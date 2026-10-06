<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\MistralAI;

use Piwik\API\Request;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\MistralAI\Services\ApiConnection;
use Piwik\Plugins\MistralAI\Services\ChatRequestParser;
use Piwik\Plugins\MistralAI\Services\DataPrivacy;
use Piwik\Plugins\MistralAI\Services\InsightNotAvailableException;
use Piwik\Plugins\MistralAI\Services\InsightReport;
use Piwik\Plugins\MistralAI\Services\RateLimitExceededException;
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

    public function __construct(\Piwik\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getResponse(int $idSite, string $period, string $date, $messages = []): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $idSite = (int) Common::getRequestVar('idSite');
        Piwik::checkUserHasViewAccess($idSite);

        // Get messages from request if not passed or if passed as JSON string
        $messages = StaticContainer::get(ChatRequestParser::class)->parseMessages($messages);

        $dataSharingError = $this->getDataSharingError();
        if ($dataSharingError !== null) {
            return ['error' => $dataSharingError];
        }

        $rateLimitError = $this->getRateLimitError($idSite);
        if ($rateLimitError !== null) {
            return ['error' => $rateLimitError];
        }

        $settings = EffectiveSettings::forSite($idSite);
        $chatBasePrompt = $settings->getChatBasePrompt();

        $conversationBase = [
            [
                "role" => "system",
                "content" => $chatBasePrompt,
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
        $messages = StaticContainer::get(ChatRequestParser::class)->parseMessages($messages);
        $widgetParams = StaticContainer::get(ChatRequestParser::class)->parseWidgetParams($widgetParams);

        $dataSharingError = $this->getDataSharingError();
        if ($dataSharingError !== null) {
            return ['error' => $dataSharingError];
        }

        $rateLimitError = $this->getRateLimitError($idSite);
        if ($rateLimitError !== null) {
            return ['error' => $rateLimitError];
        }

        $settings = EffectiveSettings::forSite($idSite);
        $insightBasePrompt = $settings->getInsightBasePrompt();

        $insight = $this->fetchInsightData($widgetParams, $idSite, $date, $period);
        if (isset($insight['error'])) {
            return ['error' => $insight['error']];
        }
        $data = $insight['data'];
        // the insights panel opens without a question, or ends with its previous answer when opened again
        $messages = StaticContainer::get(ChatRequestParser::class)->endWithQuestion($messages, Piwik::translate('MistralAI_InsightAgentPrompt'));

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
        $messages = StaticContainer::get(ChatRequestParser::class)->parseMessages($messages);
        $widgetParams = StaticContainer::get(ChatRequestParser::class)->parseWidgetParams($widgetParams);

        $dataSharingError = $this->getDataSharingError();
        if ($dataSharingError !== null) {
            $this->streamError($dataSharingError['message']);
            return;
        }

        $rateLimitError = $this->getRateLimitError($idSite);
        if ($rateLimitError !== null) {
            $this->streamError($rateLimitError['message']);
            return;
        }

        $settings = EffectiveSettings::forSite($idSite);

        if (StaticContainer::get(InsightReport::class)->isInsightRequest($widgetParams)) {
            // Insight mode: fetch report data and use insight prompt
            $insightBasePrompt = $settings->getInsightBasePrompt();

            $insight = $this->fetchInsightData($widgetParams, $idSite, $date, $period);
            if (isset($insight['error'])) {
                $this->streamError($insight['error']['message']);
                return;
            }
            $data = $insight['data'];
            // the insights panel opens without a question, or ends with its previous answer when opened again
            $messages = StaticContainer::get(ChatRequestParser::class)->endWithQuestion($messages, Piwik::translate('MistralAI_InsightAgentPrompt'));

            $conversationBase = [
                [
                    "role" => "system",
                    "content" => "$insightBasePrompt $data",
                ]
            ];
        } else {
            // Regular chat mode
            $chatBasePrompt = $settings->getChatBasePrompt();

            $conversationBase = [
                [
                    "role" => "system",
                    "content" => $chatBasePrompt,
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
            return ['data' => StaticContainer::get(InsightReport::class)->fetch($widgetParams, $idSite, $date, $period)];
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

        echo "data: " . json_encode(['error' => ['message' => $message]], JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n";
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
     * @param string|null $dataSharingAllowed "1" lets the plugin send Matomo data to the AI provider, off by default
     */
    public function setSystemSettings(
        ?string $host = null,
        ?string $apiKey = null,
        ?string $modelPreset = null,
        ?string $modelCustom = null,
        ?string $agentModel = null,
        ?string $chatBasePrompt = null,
        ?string $insightBasePrompt = null,
        bool $deleteApiKey = false,
        ?string $dataSharingAllowed = null,
        ?string $maskPersonalData = null,
        ?string $stripUrlQueryStrings = null,
        ?string $excludeVisitorData = null
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
            'dataSharingAllowed' => $dataSharingAllowed,
            'maskPersonalData' => $maskPersonalData,
            'stripUrlQueryStrings' => $stripUrlQueryStrings,
            'excludeVisitorData' => $excludeVisitorData,
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
     * Validates that the URL is a valid HTTPS API endpoint, with the rule of the general settings
     */
    private function isValidApiUrl(?string $url): bool
    {
        return SystemSettings::isHttpsUrl((string) $url);
    }

    /**
     * Sends a conversation to the AI model and returns the response
     *
     * @throws Exception if configuration is missing or API call fails
     */
    private function fetchModelAi(array $conversation, EffectiveSettings $settings): array
    {
        $config = ApiConnection::fromSettings($settings);

        // Sanitize conversation messages
        $sanitizedConversation = $this->sanitizeConversation($conversation);

        $data = [
            "model" => $config['model'],
            "messages" => $sanitizedConversation,
        ];

        $headers = ApiConnection::headers($config['apiKey']);

        $this->logger->info('MistralAI API request to model: ' . $config['model'] . ' at ' . $config['host']);

        $ch = curl_init($config['host']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
            $this->collectHeader($header, $responseHeaders);
            return strlen($header);
        });

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
        $config = ApiConnection::fromSettings($settings);
        $sanitizedConversation = $this->sanitizeConversation($conversation);

        $data = [
            "model" => $config['model'],
            "messages" => $sanitizedConversation,
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, ApiConnection::headers($config['apiKey'], 'text/event-stream'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); // No timeout for streaming
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

        // Error responses are plain JSON, not SSE: they are buffered and sent as an SSE error event
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
                $errorBuffer .= $chunk;
            }
            return strlen($chunk);
        });

        curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            echo "data: " . json_encode(['error' => ['message' => $error]], JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n";
            flush();
        } elseif ($errorBuffer !== '' || ($httpCode !== 0 && $httpCode !== 200)) {
            $reason = ModelUpgradeNotice::classifyApiError($httpCode, $errorBuffer, $responseHeaders);
            if ($reason !== null) {
                $apiError = ModelUpgradeNotice::build($reason, $settings);
            } else {
                $apiError = ['message' => $this->extractApiErrorMessage($errorBuffer, $httpCode, $config['model'])];
            }
            $this->logger->warning('MistralAI streaming API error (HTTP ' . $httpCode . '): ' . substr($errorBuffer, 0, 500));
            echo "data: " . json_encode(['error' => $apiError], JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n";
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
     * Extracts a human-readable error message from an error response body
     */
    private function extractApiErrorMessage(string $body, int $httpCode, string $model): string
    {
        $body = trim($body);
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                if (isset($decoded['error']) && is_array($decoded['error']) && !empty($decoded['error']['message'])) {
                    return (string) $decoded['error']['message'];
                }
                if (isset($decoded['error']) && is_string($decoded['error']) && $decoded['error'] !== '') {
                    return $decoded['error'];
                }
                if (!empty($decoded['message']) && is_string($decoded['message'])) {
                    return $decoded['message'];
                }
            }
            $snippet = preg_replace('/\s+/', ' ', $body);
            if (mb_strlen($snippet) > 300) {
                $snippet = mb_substr($snippet, 0, 300) . '...';
            }
            return 'API error (HTTP ' . $httpCode . ') for model "' . $model . '": ' . $snippet;
        }

        return 'API request failed (HTTP ' . $httpCode . ') for model "' . $model . '" with no response body';
    }


    /**
     * Messages with an allowed role, without the name field that the Mistral AI API rejects
     */
    private function sanitizeConversation(array $conversation): array
    {
        return array_map(static function (array $message): array {
            unset($message['name']);
            return $message;
        }, StaticContainer::get(ChatRequestParser::class)->sanitizeConversation($conversation));
    }

    /**
     * The error displayed instead of an answer while a super user has not allowed sending Matomo data to the provider
     *
     * @return array{message: string}|null
     */
    private function getDataSharingError(): ?array
    {
        $message = StaticContainer::get(DataPrivacy::class)->getDataSharingError();

        return $message === null ? null : ['message' => $message];
    }

    /**
     * The refusal of a request over the rate limit, answered like the other errors so the user reads it
     *
     * @return array{message: string}|null
     */
    private function getRateLimitError(int $idSite): ?array
    {
        try {
            StaticContainer::get(RateLimiter::class)->check($idSite);
        } catch (RateLimitExceededException $e) {
            return ['message' => $e->getMessage()];
        }

        return null;
    }
}
