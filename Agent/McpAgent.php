<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\Agent;

use Piwik\API\Request;
use Piwik\Log\LoggerInterface;
use Piwik\NoAccessException;
use Piwik\Piwik;
use Piwik\Plugins\MistralAI\Settings\EffectiveSettings;
use Piwik\Plugins\MistralAI\Settings\ModelUpgradeNotice;
use Piwik\Site;

/**
 * Chat agent running on the Mistral AI API (host and key of the plugin settings) with the tools of the McpServer
 * plugin, called in-process with the permissions of the current user: no public MCP endpoint or OAuth client is
 * involved. Without McpServer, the chat of the plugin answers as before.
 *
 * McpServer only accepts internal tool calls when the root request is not an API request, so the agent must be run
 * from a controller action.
 */
class McpAgent
{
    public const MODE_AGENT = 'agent';
    public const MODE_CHAT = 'chat';

    public const STATUS_READY = 'ready';
    public const STATUS_NOT_INSTALLED = 'not_installed';
    public const STATUS_NOT_ACTIVATED = 'not_activated';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_NO_ACCESS = 'no_access';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public const MAX_ITERATIONS = 10;
    public const MAX_TOKENS = 4096;
    public const TIMEOUT_SECONDS = 120;

    // the free plans of Mistral AI allow about one request per second: the requests of a turn are spaced and a
    // rate limited request is retried a few times before the turn stops
    public const MIN_SECONDS_BETWEEN_REQUESTS = 1.0;
    public const MAX_RETRIES = 2;
    public const RETRY_DELAYS_SECONDS = [2, 5];
    public const MAX_RETRY_AFTER_SECONDS = 10;
    private const RETRYABLE_HTTP_CODES = [429, 502, 503, 504];

    public const CONFIRMATION_RULE = 'Before any tool call that creates, modifies or deletes something in Matomo, describe the exact change and ask the user to confirm it explicitly in the conversation. Only make that tool call after the user has confirmed it in a later message.';

    // referenced by name: McpServer is an optional Marketplace plugin
    private const MCP_UNAVAILABLE_EXCEPTION = 'Piwik\Plugins\McpServer\Support\Access\McpUnavailableException';

    /** @var list<array<string, mixed>>|null */
    private ?array $toolCatalog = null;

    private PluginDependencies $dependencies;

    private ?float $lastRequestAt = null;

    public function __construct(private LoggerInterface $logger, ?PluginDependencies $dependencies = null)
    {
        $this->dependencies = $dependencies ?? new PluginDependencies();
    }

    /**
     * Whether a tool can change Matomo. Only read-only tools are exposed until a super user allows
     * the raw API access (create, update, delete methods) in the McpServer settings.
     *
     * @param list<array<string, mixed>> $tools
     */
    public static function hasActionTools(array $tools): bool
    {
        foreach ($tools as $tool) {
            if (($tool['readOnly'] ?? null) !== true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string|int> $urlParams idSite, period and date of the current page, for the links of the
     *                                             recommendations
     * @return array{mode: string, keySource: string, mcp: string, model: string, toolCount: int,
     *               canPerformActions: bool, recommendations: list<array<string, mixed>>}
     */
    public function getStatus(int $idSite = 0, array $urlParams = []): array
    {
        $settings = $this->getSettings($idSite);
        $mcpStatus = $this->getMcpStatus();
        $tools = $mcpStatus === self::STATUS_READY ? $this->getToolCatalog() : [];
        $canPerformActions = self::hasActionTools($tools);
        $isConfigured = $settings->isConfigured();

        // without a Mistral AI key there is no chat at all, unlocking the tools would not help
        $recommendations = [];
        if ($isConfigured) {
            $recommendations = Recommendations::build([
                'mcp' => $mcpStatus,
                'canPerformActions' => $canPerformActions,
            ], $this->isSuperUser(), $urlParams);
        }

        return [
            'mode' => $isConfigured && $mcpStatus === self::STATUS_READY ? self::MODE_AGENT : self::MODE_CHAT,
            'keySource' => $settings->getKeySource(),
            'mcp' => $mcpStatus,
            'model' => $settings->getAgentModel(),
            'toolCount' => count($tools),
            'canPerformActions' => $canPerformActions,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Whether the Matomo tools of McpServer can be used by the current user
     */
    public function hasTools(): bool
    {
        return $this->getAvailableTools() !== [];
    }

    public function buildSystemPrompt(
        string $basePrompt,
        int $idSite,
        string $period,
        string $date,
        ?string $reportData = null,
        bool $withTools = true
    ): string {
        $lines = [trim($basePrompt), ''];
        if ($withTools) {
            $lines[] = 'You are connected to this Matomo instance through Matomo tools (MCP). Use them to look up real analytics data and to perform the actions the user asks for, then answer with the results.';
            // the small Mistral AI models otherwise guess API method names and repeat the same failing call
            $lines[] = 'Never guess a Matomo API method name: look it up with the tools that list or describe the API methods, then use its exact name. When a tool returns an error, fix the arguments as the error explains instead of repeating the same call. Before changing or deleting anything, check with the tools that you target the right item.';
            // kept out of the editable base prompt, so an administrator rewriting it cannot remove this safeguard
            if (self::hasActionTools($this->getAvailableTools())) {
                $lines[] = self::CONFIRMATION_RULE;
            }
        }
        $lines = array_merge($lines, [
            sprintf(
                'Current context: website "%s" (idSite %d), period "%s", date "%s". Use this website and period unless the user asks for something else.',
                Site::getNameFor($idSite),
                $idSite,
                $period,
                $date
            ),
            'Answer in the language of the user. Format your answer as readable Markdown text (titles, lists, bold metrics), never as raw JSON.',
        ]);

        if ($reportData !== null) {
            $lines[] = '';
            $lines[] = $withTools
                ? 'The data of the report the user is currently looking at is provided below in JSON. Analyze it directly: only use the tools when you need additional data to answer.'
                : 'The data of the report the user is currently looking at is provided below in JSON. Analyze it directly.';
            if ($withTools) {
                $lines[] = 'Its "request" object is the exact Matomo API request behind this data (method, idSite, period, date, segment, idSubtable...): to fetch more rows, a subtable or another period to compare with, call the Matomo tools with exactly these parameters, changing only what you need.';
            }
            $lines[] = $reportData;
        }

        return implode("\n", $lines);
    }

    /**
     * Runs the conversation until the model answers without calling tools
     *
     * @param list<array{role: string, content: string}> $messages user and assistant messages, oldest first
     * @param callable(string, array<string, mixed>): void $emit receives the agent events
     */
    public function run(array $messages, string $systemPrompt, EffectiveSettings $settings, string $sessionKey, callable $emit): void
    {
        $catalog = $this->getAvailableTools();
        $toolTitles = [];
        foreach ($catalog as $tool) {
            $toolTitles[$tool['name']] = !empty($tool['title']) ? $tool['title'] : $tool['name'];
        }
        $tools = MistralFormat::toTools($catalog);

        $conversation = array_merge([['role' => 'system', 'content' => $systemPrompt]], $this->toMistralMessages($messages));

        for ($iteration = 0; $iteration < self::MAX_ITERATIONS; $iteration++) {
            // the user closed the chat: do not send another request for nobody
            if ($iteration > 0 && $this->isClientGone()) {
                return;
            }

            try {
                $response = $this->converse($this->buildPayload($settings, $conversation, $tools, 'auto'), $settings);
            } catch (MistralApiException $e) {
                $this->logger->warning('MistralAI agent: request failed (HTTP {code}): {message}', [
                    'code' => $e->getHttpCode(),
                    'message' => $e->getMessage(),
                ]);
                $emit('error', $this->getErrorEvent($e, $settings));
                return;
            }

            $choice = is_array($response['choices'][0] ?? null) ? $response['choices'][0] : [];
            $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];
            $text = MistralFormat::getText($message);
            $toolCalls = MistralFormat::getToolCalls($message);

            if (trim($text) !== '') {
                $emit('text', ['content' => trim($text)]);
            }

            if ($toolCalls === [] || ($choice['finish_reason'] ?? '') === 'length') {
                return;
            }

            $conversation[] = MistralFormat::assistantMessage($text, $toolCalls);

            foreach ($toolCalls as $toolCall) {
                $emit('tool_call', [
                    'id' => $toolCall['id'],
                    'name' => $toolCall['name'],
                    'title' => $toolTitles[$toolCall['name']] ?? $toolCall['name'],
                ]);

                $result = $toolCall['arguments'] === null
                    ? $this->errorResult('The arguments of the tool call are not a valid JSON object.')
                    : $this->callTool($toolCall['name'], $toolCall['arguments'], $sessionKey);

                $emit('tool_result', ['id' => $toolCall['id'], 'isError' => $result['isError']]);

                $conversation[] = MistralFormat::toolMessage($toolCall['id'], $toolCall['name'], $result);
            }
        }

        // the small models may keep calling tools: they answer with the data collected so far when tools are refused
        if (!$this->isClientGone()) {
            try {
                $response = $this->converse($this->buildPayload($settings, $conversation, $tools, 'none'), $settings);
                $message = is_array($response['choices'][0]['message'] ?? null) ? $response['choices'][0]['message'] : [];
                $text = trim(MistralFormat::getText($message));
                if ($text !== '') {
                    $emit('text', ['content' => $text]);
                    return;
                }
            } catch (MistralApiException $e) {
                $this->logger->warning('MistralAI agent: the final answer failed (HTTP {code}): {message}', [
                    'code' => $e->getHttpCode(),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $emit('error', ['message' => $this->translate('MistralAI_AgentMaxIterations')]);
    }

    /**
     * @param list<array<string, mixed>> $conversation
     * @param list<array<string, mixed>> $tools
     * @param string $toolChoice "auto", or "none" to get an answer without further tool calls
     * @return array<string, mixed>
     */
    private function buildPayload(EffectiveSettings $settings, array $conversation, array $tools, string $toolChoice): array
    {
        $payload = [
            'model' => $settings->getAgentModel(),
            'messages' => $conversation,
            'max_tokens' => self::MAX_TOKENS,
        ];
        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = $toolChoice;
        }

        return $payload;
    }

    /**
     * One request to the Mistral AI chat completions API, spaced from the previous one and retried when rate limited
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed> the decoded response
     * @throws MistralApiException
     */
    protected function converse(array $payload, EffectiveSettings $settings): array
    {
        $host = $settings->getHost();
        if (!$this->isValidHost($host)) {
            throw new MistralApiException('Invalid API host URL - HTTPS required');
        }

        for ($attempt = 0; ; $attempt++) {
            $this->waitForRequestSlot();
            $response = $this->sendRequest($host, $settings->getApiKey(), $payload);
            $this->lastRequestAt = $this->now();

            if ($response['error'] !== '') {
                throw new MistralApiException('Connection error: ' . $response['error']);
            }

            $status = $response['status'];
            $body = $response['body'];
            if ($status === 200) {
                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    throw new MistralApiException('Invalid JSON response from the Mistral AI API', $status);
                }

                return $decoded;
            }

            $reason = ModelUpgradeNotice::classifyApiError($status, $body, $response['headers']);
            $retryable = in_array($status, self::RETRYABLE_HTTP_CODES, true) && $reason !== ModelUpgradeNotice::REASON_NOT_IN_PLAN;
            if (!$retryable || $attempt >= self::MAX_RETRIES) {
                throw new MistralApiException(self::extractErrorMessage($body, $status), $status, $reason);
            }

            $delay = self::getRetryDelay($response['headers'], $attempt);
            $this->logger->info('MistralAI agent: HTTP {code}, retrying in {delay}s', ['code' => $status, 'delay' => $delay]);
            $this->pause($delay);
        }
    }

    /**
     * HTTP transport
     *
     * @param array<string, mixed> $payload
     * @return array{status: int, headers: array<string, string>, body: string, error: string} headers in lowercase
     */
    protected function sendRequest(string $host, string $apiKey, array $payload): array
    {
        $headers = [];
        $httpHeaders = ['Content-Type: application/json', 'Accept: application/json'];
        if ($apiKey !== '') {
            $httpHeaders[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init($host);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_POSTFIELDS, (string) json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$headers) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($header);
        });

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'status' => $status,
            'headers' => $headers,
            'body' => is_string($body) ? $body : '',
            'error' => $error,
        ];
    }

    protected function now(): float
    {
        return microtime(true);
    }

    protected function pause(float $seconds): void
    {
        if ($seconds > 0) {
            usleep((int) round($seconds * 1000000));
        }
    }

    protected function isClientGone(): bool
    {
        return connection_aborted() === 1;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchToolCatalog(): array
    {
        $catalog = Request::processRequest('McpServer.getInternalToolCatalog', [], []);

        return is_array($catalog) ? array_values($catalog) : [];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    protected function callInternalTool(string $name, array $arguments, string $sessionKey): array
    {
        $result = Request::processRequest('McpServer.callInternalTool', [
            'name' => $name,
            'arguments' => $arguments,
            'sessionKey' => $sessionKey,
        ], []);

        return is_array($result) ? $result : [];
    }

    protected function translate(string $translationKey): string
    {
        return Piwik::translate($translationKey);
    }

    /**
     * Error event of a failed request: a model the plan does not allow, or a persistent rate limit, links to the
     * settings where the user can choose another model
     *
     * @return array<string, string>
     */
    protected function getErrorEvent(MistralApiException $exception, EffectiveSettings $settings): array
    {
        if ($exception->getReason() !== null) {
            return ModelUpgradeNotice::build($exception->getReason(), $settings, $settings->getAgentModel(), $settings->getAgentModelSource());
        }

        return ['message' => $exception->getMessage()];
    }

    protected function getSettings(int $idSite): EffectiveSettings
    {
        return EffectiveSettings::forSite($idSite);
    }

    protected function isSuperUser(): bool
    {
        return Piwik::hasUserSuperUserAccess();
    }

    /**
     * @param array<string, string> $headers
     */
    public static function getRetryDelay(array $headers, int $attempt): float
    {
        $retryAfter = trim($headers['retry-after'] ?? '');
        if ($retryAfter !== '' && is_numeric($retryAfter)) {
            return (float) min(max((float) $retryAfter, 0.0), self::MAX_RETRY_AFTER_SECONDS);
        }

        return (float) self::RETRY_DELAYS_SECONDS[min($attempt, count(self::RETRY_DELAYS_SECONDS) - 1)];
    }

    /**
     * Human-readable message of an error answer of the API
     */
    public static function extractErrorMessage(string $body, int $httpCode): string
    {
        $decoded = json_decode(trim($body), true);
        if (is_array($decoded)) {
            if (is_array($decoded['error'] ?? null) && is_string($decoded['error']['message'] ?? null) && $decoded['error']['message'] !== '') {
                return $decoded['error']['message'];
            }
            if (is_string($decoded['message'] ?? null) && $decoded['message'] !== '') {
                return $decoded['message'];
            }
            // validation errors of the API: the message is a list of details
            if (is_array($decoded['message'] ?? null)) {
                return 'API error (HTTP ' . $httpCode . '): ' . mb_substr((string) json_encode($decoded['message']), 0, 300);
            }
        }

        $snippet = trim((string) preg_replace('/\s+/', ' ', $body));
        if ($snippet === '') {
            return 'API request failed (HTTP ' . $httpCode . ') with no response body';
        }

        return 'API error (HTTP ' . $httpCode . '): ' . mb_substr($snippet, 0, 300);
    }

    private function waitForRequestSlot(): void
    {
        if ($this->lastRequestAt === null) {
            return;
        }

        $wait = $this->lastRequestAt + self::MIN_SECONDS_BETWEEN_REQUESTS - $this->now();
        if ($wait > 0) {
            $this->pause($wait);
        }
    }

    private function isValidHost(string $host): bool
    {
        $parsed = parse_url($host);

        return is_array($parsed) && ($parsed['scheme'] ?? '') === 'https' && !empty($parsed['host']);
    }

    private function getMcpStatus(): string
    {
        $pluginState = $this->dependencies->getPluginState(PluginDependencies::MCP_SERVER);
        if ($pluginState === PluginDependencies::PLUGIN_MISSING) {
            return self::STATUS_NOT_INSTALLED;
        }
        if ($pluginState === PluginDependencies::PLUGIN_INACTIVE) {
            return self::STATUS_NOT_ACTIVATED;
        }

        try {
            $this->getToolCatalog();
            return self::STATUS_READY;
        } catch (\Throwable $e) {
            if (is_a($e, self::MCP_UNAVAILABLE_EXCEPTION)) {
                return self::STATUS_DISABLED;
            }
            if ($e instanceof NoAccessException) {
                return self::STATUS_NO_ACCESS;
            }

            $this->logger->warning('MistralAI agent: MCP tools are unavailable: ' . $e->getMessage());
            return self::STATUS_UNAVAILABLE;
        }
    }

    /**
     * The tools of McpServer, none when it is unavailable
     *
     * @return list<array<string, mixed>>
     */
    private function getAvailableTools(): array
    {
        if ($this->getMcpStatus() !== self::STATUS_READY) {
            return [];
        }

        try {
            return $this->getToolCatalog();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getToolCatalog(): array
    {
        if ($this->toolCatalog === null) {
            $this->toolCatalog = $this->fetchToolCatalog();
        }

        return $this->toolCatalog;
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array{content: list<array<string, mixed>>, structuredContent: array<string, mixed>|null, isError: bool}
     */
    private function callTool(string $name, array $arguments, string $sessionKey): array
    {
        try {
            $result = $this->callInternalTool($name, $arguments, $sessionKey);

            return [
                'content' => is_array($result['content'] ?? null) ? $result['content'] : [],
                'structuredContent' => is_array($result['structuredContent'] ?? null) ? $result['structuredContent'] : null,
                'isError' => !empty($result['isError']),
            ];
        } catch (\Throwable $e) {
            // reported to the model, which can explain the failure or try something else
            return $this->errorResult($e->getMessage());
        }
    }

    /**
     * @return array{content: list<array<string, mixed>>, structuredContent: null, isError: bool}
     */
    private function errorResult(string $message): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $message]],
            'structuredContent' => null,
            'isError' => true,
        ];
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     * @return list<array{role: string, content: string}>
     */
    private function toMistralMessages(array $messages): array
    {
        $mistralMessages = [];
        foreach ($messages as $message) {
            if (!in_array($message['role'] ?? '', ['user', 'assistant'], true) || trim((string) ($message['content'] ?? '')) === '') {
                continue;
            }
            $mistralMessages[] = ['role' => $message['role'], 'content' => (string) $message['content']];
        }

        return $mistralMessages;
    }
}
