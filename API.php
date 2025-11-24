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
use Piwik\Cache;
use Piwik\Common;
use Piwik\Option;
use Piwik\Piwik;
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

    /**
     * Cache TTL for models list (1 hour)
     */
    private const MODELS_CACHE_TTL = 3600;

    /**
     * Rate limit settings
     */
    private const RATE_LIMIT_REQUESTS = 30;
    private const RATE_LIMIT_WINDOW = 3600; // 1 hour

    public function __construct(\Piwik\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getResponse(int $idSite, string $period, string $date, $messages = []): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $idSite = (int) Common::getRequestVar('idSite');
        Piwik::checkUserHasViewAccess($idSite);

        // Check rate limit
        $this->checkRateLimit($idSite);

        // Handle JSON-encoded messages from POST body
        if (is_string($messages)) {
            $messages = json_decode($messages, true) ?: [];
        }
        if (!is_array($messages)) {
            $messages = [];
        }

        $systemSettings = new SystemSettings();
        $measurableSettings = new MeasurableSettings($idSite);
        $chatBasePrompt = $measurableSettings->chatBasePrompt->getValue() ?: $systemSettings->chatBasePrompt->getValue();

        // Mistral requires at least one user message
        if (empty($messages)) {
            return ['error' => ['message' => 'Please enter a message']];
        }

        $conversationBase = [
            [
                "role" => "system",
                "content" => $chatBasePrompt,
            ]
        ];

        return $this->fetchModelAi(array_merge($conversationBase, $messages), $idSite);
    }

    public function getInsights(int $idSite, string $period, string $date, array $messages = [], array $widgetParams = []): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $idSite = (int) Common::getRequestVar('idSite');
        Piwik::checkUserHasViewAccess($idSite);

        // Check rate limit
        $this->checkRateLimit($idSite);

        $systemSettings = new SystemSettings();
        $measurableSettings = new MeasurableSettings($idSite);
        $insightBasePrompt = $measurableSettings->insightBasePrompt->getValue() ?: $systemSettings->insightBasePrompt->getValue();

        $requestParams = $this->buildRequestParams($widgetParams, $idSite, $date, $period);
        $apiMethod = $this->resolveReportMethod($requestParams['_apiMethod'], $widgetParams);
        unset($requestParams['_apiMethod']);

        // Validate API method format (Module.action)
        if (!preg_match('/^[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/', $apiMethod)) {
            throw new Exception('Invalid API method format');
        }

        // Matomo's Request::processRequest handles permission checks internally
        $data = Request::processRequest($apiMethod, $requestParams);

        $conversationBase = [
            [
                "role" => "system",
                "content" => $insightBasePrompt,
            ],
            [
                "role" => "user",
                "content" => "Analyze this data: $data",
            ]
        ];

        return $this->fetchModelAi(array_merge($conversationBase, $messages), $idSite);
    }

    /**
     * Streams a response from the AI model using Server-Sent Events
     * Call this endpoint directly for streaming support
     */
    public function getStreamingResponse(int $idSite, string $period, string $date, $messages = []): void
    {
        Piwik::checkUserHasSomeViewAccess();

        $idSite = (int) Common::getRequestVar('idSite');
        Piwik::checkUserHasViewAccess($idSite);

        $this->checkRateLimit($idSite);

        // Get messages from POST body - try multiple methods
        $messagesRaw = '';
        if (isset($_POST['messages'])) {
            $messagesRaw = $_POST['messages'];
        } elseif (isset($_REQUEST['messages'])) {
            $messagesRaw = $_REQUEST['messages'];
        } else {
            $messagesRaw = Common::getRequestVar('messages', '', 'string');
        }

        if (!empty($messagesRaw) && is_string($messagesRaw)) {
            $messages = json_decode($messagesRaw, true) ?: [];
        } elseif (is_string($messages) && !empty($messages)) {
            $messages = json_decode($messages, true) ?: [];
        }
        if (!is_array($messages)) {
            $messages = [];
        }

        $systemSettings = new SystemSettings();
        $measurableSettings = new MeasurableSettings($idSite);
        $chatBasePrompt = $measurableSettings->chatBasePrompt->getValue() ?: $systemSettings->chatBasePrompt->getValue();

        $conversationBase = [
            [
                "role" => "system",
                "content" => $chatBasePrompt,
            ]
        ];

        $this->streamModelAi(array_merge($conversationBase, $messages), $idSite);
    }

    /**
     * Check and enforce rate limits per site
     * @throws Exception if rate limit exceeded
     */
    private function checkRateLimit(int $idSite): void
    {
        $userLogin = Piwik::getCurrentUserLogin();
        $rateLimitKey = 'MistralAI_ratelimit_' . $idSite . '_' . $userLogin;

        $currentTime = time();
        $rateData = Option::get($rateLimitKey);

        if ($rateData) {
            $rateData = json_decode($rateData, true);
            $windowStart = $rateData['window_start'] ?? 0;
            $requestCount = $rateData['count'] ?? 0;

            // Reset window if expired
            if ($currentTime - $windowStart > self::RATE_LIMIT_WINDOW) {
                $rateData = ['window_start' => $currentTime, 'count' => 0];
            }

            // Check limit
            if ($rateData['count'] >= self::RATE_LIMIT_REQUESTS) {
                $resetTime = $windowStart + self::RATE_LIMIT_WINDOW - $currentTime;
                throw new Exception("Rate limit exceeded. Please wait {$resetTime} seconds before making another request.");
            }
        } else {
            $rateData = ['window_start' => $currentTime, 'count' => 0];
        }

        // Increment counter
        $rateData['count']++;
        Option::set($rateLimitKey, json_encode($rateData));
    }

    /**
     * Builds request parameters from widget parameters with proper validation
     */
    private function buildRequestParams(array $widgetParams, int $idSite, string $date, string $period): array
    {
        // Check if this is an evolution graph that needs multiple data points
        $action = isset($widgetParams['action']) ? $widgetParams['action'] : '';
        $isEvolutionGraph = in_array($action, ['getEvolutionGraph', 'getEvolutionOverview', 'getRowEvolution'], true);

        // For evolution graphs, use day period with last90 to get multiple data points
        if ($isEvolutionGraph) {
            $period = 'day';
            $date = 'last90';
        }

        $requestParams = [
            'idSite' => $idSite,
            'date' => $this->sanitizeDate($date),
            'period' => $this->sanitizePeriod($period),
            'format' => 'json',
        ];

        // Sanitize module and action (alphanumeric only)
        $module = isset($widgetParams['module']) ? preg_replace('/[^a-zA-Z0-9]/', '', $widgetParams['module']) : '';
        $action = isset($widgetParams['action']) ? preg_replace('/[^a-zA-Z0-9]/', '', $widgetParams['action']) : '';
        $requestParams['_apiMethod'] = $module . '.' . $action;

        // Define supported parameters with their validation rules
        $supportedParams = [
            // Standard Matomo API parameters
            'idSubtable' => 'int',
            'idAlert' => 'int',
            'idGoal' => 'int',
            'idDimension' => 'int',
            'idNote' => 'int',
            'idExperiment' => 'int',
            'idCustomReport' => 'int',
            'idExport' => 'int',
            'idLogCrash' => 'int',
            'idFailure' => 'int',
            // Premium plugin parameters
            'idForm' => 'int',
            'idFunnel' => 'int',
            'idHeatmap' => 'int',
            'idSessionRecording' => 'int',
            // Common parameters
            'segment' => 'segment',
            'flat' => 'bool',
            'expanded' => 'bool',
            'filter_limit' => 'int',
            'filter_offset' => 'int',
        ];

        foreach ($supportedParams as $param => $type) {
            if (isset($widgetParams[$param]) && $widgetParams[$param] !== '') {
                $requestParams[$param] = $this->sanitizeParam($widgetParams[$param], $type);
            }
        }

        return $requestParams;
    }

    /**
     * Sanitizes a parameter value based on its type
     */
    private function sanitizeParam($value, string $type)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'bool':
                return $value ? 1 : 0;
            case 'segment':
                return Common::unsanitizeInputValue($value);
            default:
                return Common::sanitizeInputValue($value);
        }
    }

    /**
     * Validates and sanitizes date parameter
     */
    private function sanitizeDate(string $date): string
    {
        if (preg_match('/^(today|yesterday|last\d+|previous\d+|\d{4}-\d{2}-\d{2}(,\d{4}-\d{2}-\d{2})?)$/', $date)) {
            return $date;
        }
        return 'today';
    }

    /**
     * Validates and sanitizes period parameter
     */
    private function sanitizePeriod(string $period): string
    {
        $allowedPeriods = ['day', 'week', 'month', 'year', 'range'];
        return in_array($period, $allowedPeriods, true) ? $period : 'day';
    }

    /**
     * Resolves the API method from widget parameters
     * Handles evolution graph controller actions by extracting the real API method
     */
    private function resolveReportMethod(string $reportId, array $widgetParams = []): string
    {
        $evolutionActions = ['getEvolutionGraph', 'getEvolutionOverview', 'getRowEvolution'];

        $parts = explode('.', $reportId, 2);
        if (count($parts) !== 2) {
            return $reportId;
        }

        $module = $parts[0];
        $action = $parts[1];

        if (in_array($action, $evolutionActions, true)) {
            if (!empty($widgetParams['apiMethod'])) {
                return $widgetParams['apiMethod'];
            }
            if (!empty($widgetParams['method'])) {
                return $widgetParams['method'];
            }

            // Special handling for CustomReports evolution graphs
            // CustomReports doesn't have a 'get' method, always use getCustomReport
            if ($module === 'CustomReports') {
                return 'CustomReports.getCustomReport';
            }

            return $module . '.get';
        }

        return $reportId;
    }

    /**
     * Retrieves the list of available models from the Mistral API
     * Results are cached for performance
     *
     * @param string|null $host API base URL (optional)
     * @param string|null $apiKey API Key (optional)
     * @param bool $forceRefresh Force cache refresh
     * @return array List of available models
     */
    public function getAvailableModels(?string $host = null, ?string $apiKey = null, bool $forceRefresh = false): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $systemSettings = new SystemSettings();

        $configuredHost = $host ?: $systemSettings->host->getValue();
        $configuredApiKey = $apiKey ?: $systemSettings->apiKey->getValue();

        if (!$configuredHost || !$configuredApiKey) {
            return ['error' => 'Host and API Key must be configured first', 'models' => []];
        }

        if (!$this->isValidApiUrl($configuredHost)) {
            return ['error' => 'Invalid API host URL', 'models' => []];
        }

        // Check cache first (unless force refresh)
        $cacheKey = 'MistralAI_models_' . md5($configuredHost);
        $cache = Cache::getLazyCache();

        if (!$forceRefresh) {
            $cachedModels = $cache->fetch($cacheKey);
            if ($cachedModels !== false) {
                return ['models' => $cachedModels, 'error' => null, 'cached' => true];
            }
        }

        // Fetch from API
        $baseUrl = preg_replace('#/v1/.*$#', '/v1', $configuredHost);
        $modelsUrl = $baseUrl . '/models';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $configuredApiKey,
        ];

        $ch = curl_init($modelsUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->logger->warning('MistralAI API error fetching models: ' . $curlError);
            return ['error' => 'Connection error: ' . $curlError, 'models' => []];
        }

        if ($httpCode !== 200) {
            return ['error' => 'Failed to fetch models (HTTP ' . $httpCode . ')', 'models' => []];
        }

        if (empty($response)) {
            return ['error' => 'Empty response from API', 'models' => []];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON response from API', 'models' => []];
        }

        if (!isset($data['data']) || !is_array($data['data'])) {
            return ['error' => 'Invalid response structure from API', 'models' => []];
        }

        $models = [];
        foreach ($data['data'] as $model) {
            if (!isset($model['id']) || !is_string($model['id'])) {
                continue;
            }
            $modelId = $model['id'];
            if (preg_match('/^(mistral|codestral|pixtral|ministral|open-)/i', $modelId)) {
                $models[$modelId] = $modelId;
            }
        }

        ksort($models);

        // Cache the results
        $cache->save($cacheKey, $models, self::MODELS_CACHE_TTL);

        return ['models' => $models, 'error' => null, 'cached' => false];
    }

    /**
     * Clears the models cache
     */
    public function clearModelsCache(): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $systemSettings = new SystemSettings();
        $configuredHost = $systemSettings->host->getValue();

        if ($configuredHost) {
            $cacheKey = 'MistralAI_models_' . md5($configuredHost);
            $cache = Cache::getLazyCache();
            $cache->delete($cacheKey);
        }

        return ['success' => true];
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
    private function fetchModelAi(array $conversation, int $idSite): array
    {
        $config = $this->getAiConfig($idSite);

        // Sanitize conversation messages
        $sanitizedConversation = $this->sanitizeConversation($conversation);

        $data = [
            "model" => $config['model'],
            "messages" => $sanitizedConversation,
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $config['apiKey'],
        ];

        $ch = curl_init($config['host']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->logger->info('MistralAI API request to model: ' . $config['model']);

        if ($curlError) {
            $this->logger->error('MistralAI API curl error: ' . $curlError);
            throw new Exception('Connection error: ' . $curlError);
        }

        if (empty($response)) {
            throw new Exception('Empty response from MistralAI API');
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => ['message' => 'Invalid response from MistralAI API (HTTP ' . $httpCode . ')']];
        }

        // Handle errors - return as error array for frontend display
        if ($httpCode !== 200) {
            $errorMessage = $this->extractMistralError($result, $httpCode);
            return ['error' => ['message' => $errorMessage]];
        }

        // Also check for error in successful response (shouldn't happen but just in case)
        if (isset($result['error'])) {
            $errorMessage = $result['error']['message'] ?? $result['error'] ?? 'Unknown API error';
            return ['error' => ['message' => $errorMessage]];
        }

        return $result;
    }

    /**
     * Extracts error message from Mistral API response
     * Mistral uses different error formats than OpenAI
     */
    private function extractMistralError(array $result, int $httpCode): string
    {
        // Mistral format: {"message": "..."} or {"detail": "..."}
        if (isset($result['message'])) {
            return $result['message'];
        }

        if (isset($result['detail'])) {
            return is_string($result['detail']) ? $result['detail'] : json_encode($result['detail']);
        }

        // OpenAI-like format: {"error": {"message": "..."}}
        if (isset($result['error']['message'])) {
            return $result['error']['message'];
        }

        if (isset($result['error']) && is_string($result['error'])) {
            return $result['error'];
        }

        // Fallback
        return 'API error (HTTP ' . $httpCode . ')';
    }

    /**
     * Streams a conversation response using Server-Sent Events
     * This method outputs directly to the response stream
     */
    private function streamModelAi(array $conversation, int $idSite): void
    {
        $config = $this->getAiConfig($idSite);
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['apiKey'],
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); // No timeout for streaming
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

        // Stream the response chunk by chunk
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) {
            echo $chunk;
            flush();
            return strlen($chunk);
        });

        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "data: " . json_encode(['error' => ['message' => $error]]) . "\n\n";
            flush();
        }

        echo "data: [DONE]\n\n";
        flush();
    }

    /**
     * Gets AI configuration for a site
     * @throws Exception if configuration is invalid
     */
    private function getAiConfig(int $idSite): array
    {
        $systemSettings = new SystemSettings();
        $measurableSettings = new MeasurableSettings($idSite);

        $host = trim($measurableSettings->host->getValue() ?: $systemSettings->host->getValue());
        $apiKey = trim($measurableSettings->apiKey->getValue() ?: $systemSettings->apiKey->getValue());
        $model = $systemSettings->model->getValue();

        $measurableModel = $measurableSettings->model->getValue();
        if (is_array($measurableModel) && !empty($measurableModel[0])) {
            $model = $measurableModel;
        }

        if (empty($host)) {
            throw new Exception('MistralAI host is not configured');
        }

        if (empty($apiKey)) {
            throw new Exception('MistralAI API key is not configured');
        }

        if (empty($model) || (is_array($model) && empty($model[0]))) {
            throw new Exception('MistralAI model is not configured');
        }

        if (!$this->isValidApiUrl($host)) {
            throw new Exception('Invalid API host URL - HTTPS required');
        }

        // Extract model string from array (handles both numeric and associative arrays)
        $modelString = $model;
        if (is_array($model)) {
            // For associative arrays, use the first key; for numeric arrays, use first value
            $modelString = isset($model[0]) ? $model[0] : array_key_first($model);
        }

        return [
            'host' => $host,
            'apiKey' => $apiKey,
            'model' => $modelString,
        ];
    }

    /**
     * Sanitizes conversation messages to prevent injection
     */
    private function sanitizeConversation(array $conversation): array
    {
        $sanitized = [];
        $allowedRoles = ['system', 'user', 'assistant'];

        foreach ($conversation as $message) {
            if (!is_array($message)) {
                continue;
            }

            $role = $message['role'] ?? '';
            if (!in_array($role, $allowedRoles, true)) {
                continue;
            }

            $sanitizedMessage = [
                'role' => $role,
                'content' => (string) ($message['content'] ?? ''),
            ];

            $sanitized[] = $sanitizedMessage;
        }

        return $sanitized;
    }

    /**
     * Returns plugin settings for the frontend
     */
    public function getSettings(): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $systemSettings = new SystemSettings();

        return [
            'enableStreaming' => (bool) $systemSettings->enableStreaming->getValue(),
        ];
    }

    /**
     * Returns current rate limit status for the user
     */
    public function getRateLimitStatus(int $idSite): array
    {
        Piwik::checkUserHasSomeViewAccess();

        $userLogin = Piwik::getCurrentUserLogin();
        $rateLimitKey = 'MistralAI_ratelimit_' . $idSite . '_' . $userLogin;

        $rateData = Option::get($rateLimitKey);
        $currentTime = time();

        if ($rateData) {
            $rateData = json_decode($rateData, true);
            $windowStart = $rateData['window_start'] ?? 0;
            $requestCount = $rateData['count'] ?? 0;

            if ($currentTime - $windowStart > self::RATE_LIMIT_WINDOW) {
                $requestCount = 0;
                $windowStart = $currentTime;
            }

            return [
                'requests_used' => $requestCount,
                'requests_limit' => self::RATE_LIMIT_REQUESTS,
                'requests_remaining' => max(0, self::RATE_LIMIT_REQUESTS - $requestCount),
                'reset_in_seconds' => max(0, $windowStart + self::RATE_LIMIT_WINDOW - $currentTime),
            ];
        }

        return [
            'requests_used' => 0,
            'requests_limit' => self::RATE_LIMIT_REQUESTS,
            'requests_remaining' => self::RATE_LIMIT_REQUESTS,
            'reset_in_seconds' => self::RATE_LIMIT_WINDOW,
        ];
    }
}
