<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\MistralAI;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\MistralAI\Services\ChatRequestParser;
use Piwik\Plugins\MistralAI\Services\InsightNotAvailableException;
use Piwik\Plugins\MistralAI\Services\InsightReport;
use Piwik\Plugins\MistralAI\Services\RateLimiter;
use Piwik\Plugins\MistralAI\Services\SafeErrorMessage;
use Piwik\Plugins\MistralAI\Settings\DefaultPrompts;
use Piwik\Plugins\MistralAI\Settings\EffectiveSettings;
use Piwik\Plugins\MistralAI\Settings\ModelUpgradeNotice;
use Piwik\Plugins\MistralAI\Settings\SiteSettingsStorage;
use Piwik\Plugins\MistralAI\Settings\SystemSettingsForm;
use Exception;

/**
 * API for plugin MistralAI
 *
 * @method static \Piwik\Plugins\MistralAI\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    private $logger;

    /**
     * Request timeout in seconds
     */
    private const REQUEST_TIMEOUT = 60;

    public function __construct(
        \Piwik\Log\LoggerInterface $logger,
        private ChatRequestParser $requestParser,
        private InsightReport $insightReport,
        private RateLimiter $rateLimiter
    ) {
        $this->logger = $logger;
    }

    public function getResponse(int $idSite, string $period, string $date, $messages = []): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $idSite = (int) Common::getRequestVar('idSite');
        Piwik::checkUserHasViewAccess($idSite);

        // Get messages from request if not passed or if passed as JSON string
        $messages = $this->requestParser->parseMessages($messages);

        $this->rateLimiter->check($idSite);

        $settings = EffectiveSettings::forSite($idSite);

        // Mistral does not accept a name on system messages
        $conversationBase = [
            [
                "role" => "system",
                "content" => $settings->getChatBasePrompt(),
            ]
        ];

        return $this->fetchModelAi(array_merge($conversationBase, $messages), $settings);
    }

    public function getInsights(int $idSite, string $period, string $date, $messages = [], $widgetParams = []): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $idSite = (int) Common::getRequestVar('idSite');
        Piwik::checkUserHasViewAccess($idSite);

        // Parse messages and widgetParams from POST
        $messages = $this->requestParser->parseMessages($messages);
        $widgetParams = $this->requestParser->parseWidgetParams($widgetParams);

        $this->rateLimiter->check($idSite);

        $settings = EffectiveSettings::forSite($idSite);
        $insightBasePrompt = $settings->getInsightBasePrompt();

        $insight = $this->fetchInsightData($widgetParams, $idSite, $date, $period);
        if (isset($insight['error'])) {
            return ['error' => $insight['error']];
        }
        $data = $insight['data'];

        $conversationBase = [
            [
                "role" => "system",
                "content" => "$insightBasePrompt $data",
            ]
        ];

        return $this->fetchModelAi(array_merge($conversationBase, $messages), $settings);
    }

    /**
     * Streams a response from the AI model using Server-Sent Events
     * If widgetParams are present, fetches report data first (insight mode)
     */
    public function getStreamingResponse(int $idSite, string $period, string $date, $messages = [], $widgetParams = []): void
    {
        Piwik::checkUserHasSomeViewAccess();

        $idSite = (int) Common::getRequestVar('idSite');
        Piwik::checkUserHasViewAccess($idSite);

        // Parse messages and widgetParams from POST
        $messages = $this->requestParser->parseMessages($messages);
        $widgetParams = $this->requestParser->parseWidgetParams($widgetParams);

        $this->rateLimiter->check($idSite);

        $settings = EffectiveSettings::forSite($idSite);

        if ($this->insightReport->isInsightRequest($widgetParams)) {
            // Insight mode: fetch report data and use insight prompt
            $insightBasePrompt = $settings->getInsightBasePrompt();
            $insight = $this->fetchInsightData($widgetParams, $idSite, $date, $period);
            if (isset($insight['error'])) {
                $this->streamError($insight['error']['message']);
                return;
            }
            $data = $insight['data'];

            $conversationBase = [
                [
                    "role" => "system",
                    "content" => "$insightBasePrompt $data",
                ]
            ];
        } else {
            // Regular chat mode
            $conversationBase = [
                [
                    "role" => "system",
                    "content" => $settings->getChatBasePrompt(),
                ]
            ];
        }

        $this->streamModelAi(array_merge($conversationBase, $messages), $settings);
    }

    /**
     * The compact report payload of an insight, or the error to answer with instead: never a backtrace
     *
     * @return array{data?: string, error?: array{message: string}}
     */
    private function fetchInsightData(array $widgetParams, int $idSite, string $date, string $period): array
    {
        try {
            return ['data' => $this->insightReport->fetch($widgetParams, $idSite, $date, $period)];
        } catch (InsightNotAvailableException $e) {
            return ['error' => ['message' => $e->getMessage()]];
        } catch (\Throwable $e) {
            $this->logger->error('MistralAI insight error: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            return ['error' => ['message' => SafeErrorMessage::fromThrowable($e)]];
        }
    }

    /**
     * Answers a streamed request with an error, in the event format of the streamed chat completions
     */
    private function streamError(string $message): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Accel-Buffering: no');

        echo "data: " . json_encode(['error' => ['message' => $message]]) . "\n\n";
        echo "data: [DONE]\n\n";
        flush();
    }

    /**
     * Settings of a website, empty values use the general settings. The API key is replaced by a placeholder.
     *
     * @return array<string, string>
     */
    public function getSiteSettings(int $idSite): array
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $values = SiteSettingsStorage::read($idSite);
        if ($values['apiKey'] !== '') {
            $values['apiKey'] = SiteSettingsStorage::API_KEY_PLACEHOLDER;
        }

        return $values;
    }

    /**
     * Sets the settings of a website, empty values use the general settings. A parameter left out keeps its saved
     * value, so each card of the settings page saves only its own fields.
     *
     * A prompt equal to the general prompt, or to a default while the general prompt is a default too, is saved empty:
     * the website then follows the general prompt.
     *
     * @param string|null $apiKey the placeholder returned by getSiteSettings or an empty value keeps the saved key
     * @param string|null $modelPreset empty, "latest-recommended" or a model of the preset list
     * @param bool $deleteApiKey removes the API key of the website, the only way to remove it
     */
    public function setSiteSettings(
        int $idSite,
        ?string $host = null,
        ?string $apiKey = null,
        ?string $modelPreset = null,
        ?string $modelCustom = null,
        ?string $chatBasePrompt = null,
        ?string $insightBasePrompt = null,
        bool $deleteApiKey = false
    ): bool {
        Piwik::checkUserHasAdminAccess($idSite);

        $values = [];
        foreach ([
            'host' => $host,
            'apiKey' => $apiKey,
            'modelPreset' => $modelPreset,
            'modelCustom' => $modelCustom,
            'chatBasePrompt' => $chatBasePrompt,
            'insightBasePrompt' => $insightBasePrompt,
        ] as $name => $value) {
            if ($value !== null) {
                $values[$name] = trim(Common::unsanitizeInputValue($value));
            }
        }

        // only an explicit request deletes the key of the website, an empty value keeps it
        if ($deleteApiKey) {
            $values['apiKey'] = '';
        } elseif (isset($values['apiKey']) && $values['apiKey'] === '') {
            unset($values['apiKey']);
        }

        if (($values['host'] ?? '') !== '' && !$this->isValidApiUrl($values['host'])) {
            throw new Exception(Piwik::translate('MistralAI_InvalidApiUrl'));
        }

        // a saved model that is no longer listed can be kept, so the other settings can still be saved
        $modelPresetValue = $values['modelPreset'] ?? '';
        if ($modelPresetValue !== '' && $modelPresetValue !== SiteSettingsStorage::read($idSite)['modelPreset'] && !Config::isAvailableModel($modelPresetValue)) {
            throw new Exception(Piwik::translate('MistralAI_InvalidModel', [$modelPresetValue]));
        }

        if (isset($values['modelCustom']) && !preg_match('/^[A-Za-z0-9._:\/@-]{0,200}$/', $values['modelCustom'])) {
            throw new Exception(Piwik::translate('MistralAI_InvalidModel', [$values['modelCustom']]));
        }

        $generalPrompts = null;
        foreach (DefaultPrompts::SETTING_NAMES as $name => $kind) {
            if (!isset($values[$name]) || $values[$name] === '') {
                continue;
            }
            if ($generalPrompts === null) {
                $systemSettings = new SystemSettings();
                $generalPrompts = [
                    'chatBasePrompt' => $systemSettings->getChatBasePrompt(),
                    'insightBasePrompt' => $systemSettings->getInsightBasePrompt(),
                ];
            }
            $values[$name] = DefaultPrompts::toStoredSitePrompt($kind, $values[$name], $generalPrompts[$name]);
        }

        SiteSettingsStorage::save($idSite, $values);

        return true;
    }

    /**
     * Sets the general settings, edited on the Mistral AI page of the System administration. A parameter left out keeps
     * its saved value.
     *
     * @param string|null $apiKey the placeholder of the settings page keeps the saved key, like an empty value
     * @param bool $deleteApiKey removes the saved API key, the only way to remove it
     */
    public function setSystemSettings(
        ?string $host = null,
        #[\SensitiveParameter]
        ?string $apiKey = null,
        ?string $modelPreset = null,
        ?string $modelCustom = null,
        ?string $agentModel = null,
        ?string $chatBasePrompt = null,
        ?string $insightBasePrompt = null,
        bool $deleteApiKey = false
    ): bool {
        Piwik::checkUserHasSuperUserAccess();

        $values = [
            'host' => $host,
            'apiKey' => $apiKey,
            'modelPreset' => $modelPreset,
            'modelCustom' => $modelCustom,
            'agentModel' => $agentModel,
            'chatBasePrompt' => $chatBasePrompt,
            'insightBasePrompt' => $insightBasePrompt,
        ];
        foreach ($values as $name => $value) {
            if ($value !== null) {
                $values[$name] = Common::unsanitizeInputValue($value);
            }
        }

        (new SystemSettingsForm())->save($values, $deleteApiKey);

        return true;
    }

    /**
     * Validates that the URL is a valid HTTPS API endpoint
     */
    private function isValidApiUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        $parsed = parse_url($url);
        return isset($parsed['scheme']) && $parsed['scheme'] === 'https' && isset($parsed['host']);
    }

    /**
     * Sends a conversation to the AI model and returns the response
     *
     * @throws Exception if configuration is missing or API call fails
     */
    private function fetchModelAi(array $conversation, EffectiveSettings $settings): array
    {
        $config = $this->getAiConfig($settings);

        $data = [
            "model" => $config['model'],
            "messages" => $this->requestParser->sanitizeConversation($conversation),
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $config['apiKey'],
        ];

        $responseHeaders = [];

        $ch = curl_init($config['host']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
            $this->collectHeader($header, $responseHeaders);
            return strlen($header);
        });

        $this->logger->info('MistralAI API request to model: ' . $config['model'] . ' at ' . $config['host']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->logger->error('MistralAI API curl error: ' . $curlError);
            throw new Exception('Connection error: ' . $curlError);
        }

        if (empty($response)) {
            throw new Exception('Empty response from MistralAI API');
        }

        if ($httpCode !== 200) {
            $reason = ModelUpgradeNotice::classifyApiError($httpCode, (string) $response, $responseHeaders);
            if ($reason !== null) {
                $this->logger->warning('MistralAI API model error (HTTP ' . $httpCode . ') for model ' . $config['model'] . ': ' . substr($response, 0, 500));
                return ['error' => ModelUpgradeNotice::build($reason, $settings)];
            }
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('MistralAI API invalid JSON response: ' . substr($response, 0, 500));
            throw new Exception('Invalid JSON response from MistralAI API');
        }

        if (isset($result['error'])) {
            $errorMessage = $result['error']['message'] ?? 'Unknown API error';
            $this->logger->warning('MistralAI API error: ' . $errorMessage);
            return ['error' => ['message' => $errorMessage]];
        }

        if ($httpCode !== 200) {
            $this->logger->error('MistralAI API HTTP ' . $httpCode . ': ' . substr($response, 0, 500));
            return ['error' => ['message' => $this->extractApiErrorMessage((string) $response, $httpCode, $config['model'])]];
        }

        return $result;
    }

    /**
     * Streams a conversation response using Server-Sent Events
     * This method outputs directly to the response stream
     */
    private function streamModelAi(array $conversation, EffectiveSettings $settings): void
    {
        $config = $this->getAiConfig($settings);

        $data = [
            "model" => $config['model'],
            "messages" => $this->requestParser->sanitizeConversation($conversation),
            "stream" => true,
        ];

        // Disable all output buffering for streaming
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Disable PHP time limit for long streams
        set_time_limit(0);

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Nginx
        header('X-Content-Type-Options: nosniff');

        // Immediately flush headers
        flush();

        $ch = curl_init($config['host']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['apiKey'],
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); // No timeout for streaming
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

        // Track whether the upstream is actually streaming SSE; if not (e.g. error
        // responses are returned as plain JSON), buffer the body so we can surface
        // the error to the client instead of letting it fall through silently.
        $isStream = null;
        $errorBuffer = '';
        $responseHeaders = [];

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$isStream, &$responseHeaders) {
            if ($isStream === null && stripos($header, 'Content-Type:') === 0) {
                $isStream = stripos($header, 'text/event-stream') !== false;
            }
            $this->collectHeader($header, $responseHeaders);
            return strlen($header);
        });

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$isStream, &$errorBuffer) {
            if ($isStream) {
                echo $chunk;
                flush();
            } else {
                // Non-SSE response (typically an error JSON). Buffer it so we can
                // forward the message as a structured SSE error event below.
                $errorBuffer .= $chunk;
            }
            return strlen($chunk);
        });

        $this->logger->info('MistralAI streaming request to model: ' . $config['model'] . ' at ' . $config['host']);

        curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            $this->logger->error('MistralAI streaming curl error: ' . $error);
            echo "data: " . json_encode(['error' => ['message' => 'Connection error: ' . $error]]) . "\n\n";
            flush();
        } elseif ($errorBuffer !== '' || ($httpCode !== 0 && $httpCode !== 200)) {
            $reason = ModelUpgradeNotice::classifyApiError((int) $httpCode, $errorBuffer, $responseHeaders);
            if ($reason !== null) {
                $error = ModelUpgradeNotice::build($reason, $settings);
            } else {
                $error = ['message' => $this->extractApiErrorMessage($errorBuffer, $httpCode, $config['model'])];
            }
            $this->logger->warning('MistralAI streaming API error (HTTP ' . $httpCode . '): ' . substr($errorBuffer, 0, 500));
            echo "data: " . json_encode(['error' => $error]) . "\n\n";
            flush();
        }

        echo "data: [DONE]\n\n";
        flush();
    }

    /**
     * @param array<string, string> $headers lowercase header name => value
     */
    private function collectHeader(string $header, array &$headers): void
    {
        $parts = explode(':', $header, 2);
        if (count($parts) === 2) {
            $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
        }
    }

    /**
     * Extracts a human-readable error message from a non-SSE upstream response body
     */
    private function extractApiErrorMessage(string $body, int $httpCode, string $model): string
    {
        $body = trim($body);
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                // OpenAI-compatible error shape
                if (isset($decoded['error'])) {
                    if (is_array($decoded['error']) && !empty($decoded['error']['message'])) {
                        return (string) $decoded['error']['message'];
                    }
                    if (is_string($decoded['error']) && $decoded['error'] !== '') {
                        return $decoded['error'];
                    }
                }
                // Mistral error shape: {"object":"error","message":"...","type":"..."}
                if (!empty($decoded['message']) && is_string($decoded['message'])) {
                    return $decoded['message'];
                }
            }
            // Non-JSON error body (e.g. HTML from a proxy), return a truncated snippet
            $snippet = preg_replace('/\s+/', ' ', $body);
            if (mb_strlen($snippet) > 300) {
                $snippet = mb_substr($snippet, 0, 300) . '...';
            }
            return 'API error (HTTP ' . $httpCode . ') for model "' . $model . '": ' . $snippet;
        }

        return 'API request failed (HTTP ' . $httpCode . ') for model "' . $model . '" with no response body';
    }

    /**
     * Gets AI configuration for a site
     * @throws Exception if configuration is invalid
     */
    private function getAiConfig(EffectiveSettings $settings): array
    {
        $host = $settings->getHost();
        $apiKey = $settings->getApiKey();
        $model = $settings->getModel();

        if (empty($host)) {
            throw new Exception('MistralAI host is not configured');
        }

        if (empty($apiKey)) {
            throw new Exception('MistralAI API key is not configured');
        }

        if ($model === '') {
            throw new Exception('MistralAI model is not configured');
        }

        if (!$this->isValidApiUrl($host)) {
            throw new Exception('Invalid API host URL - HTTPS required');
        }

        return [
            'host' => $host,
            'apiKey' => $apiKey,
            'model' => $model,
        ];
    }
}
