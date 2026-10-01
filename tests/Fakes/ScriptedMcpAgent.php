<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI\tests\Fakes;

use Piwik\Plugins\MistralAI\Agent\McpAgent;
use Piwik\Plugins\MistralAI\Agent\MistralApiException;
use Piwik\Plugins\MistralAI\Config;
use Piwik\Plugins\MistralAI\Settings\EffectiveSettings;

/**
 * Agent with a scripted Mistral AI transport, MCP tool catalog and tool results, and a fake clock
 */
class ScriptedMcpAgent extends McpAgent
{
    /** @var list<array{status: int, headers: array<string, string>, body: string, error: string}> */
    public $httpResponses = [];

    /** @var list<array{host: string, apiKey: string, payload: array<string, mixed>, at: float}> */
    public $httpRequests = [];

    /** @var list<array<string, mixed>> */
    public $catalog = [];

    /** @var \Throwable|null thrown when the catalog is fetched */
    public $catalogError = null;

    /** @var int */
    public $catalogFetches = 0;

    /** @var list<array<string, mixed>|\Throwable> */
    public $toolResults = [];

    /** @var list<array{string, array<string, mixed>, string}> */
    public $toolCalls = [];

    /** @var float current time of the fake clock, in seconds */
    public $clock = 1000.0;

    /** @var float seconds each request takes on the fake clock */
    public $requestDuration = 0.2;

    /** @var list<float> */
    public $pauses = [];

    /** @var bool */
    public $clientGone = false;

    /** @var EffectiveSettings|null */
    public $settings = null;

    /** @var bool */
    public $superUser = true;

    /** @var bool the real error event is built with ModelUpgradeNotice, which needs a Matomo environment */
    public $realErrorEvent = false;

    public static function settings(array $site = [], array $system = []): EffectiveSettings
    {
        return EffectiveSettings::fromValues(1, $site, $system + [
            'host' => Config::DEFAULT_HOST,
            'apiKey' => 'general-key',
            'model' => 'ministral-8b-latest',
            // the tests of the loop follow the chat model, the default agent model is tested separately
            'agentModel' => Config::AGENT_MODEL_SAME_AS_CHAT,
            'chatBasePrompt' => 'Chat',
            'insightBasePrompt' => 'Insight',
        ]);
    }

    /**
     * @param array<string, mixed> $message assistant message of the Mistral AI API
     */
    public static function completion(array $message, string $finishReason = 'stop'): array
    {
        return self::http(200, [
            'id' => 'cmpl',
            'object' => 'chat.completion',
            'choices' => [['index' => 0, 'message' => ['role' => 'assistant'] + $message, 'finish_reason' => $finishReason]],
        ]);
    }

    /**
     * @param array<string, mixed>|string $body
     * @param array<string, string> $headers
     */
    public static function http(int $status, $body, array $headers = []): array
    {
        return [
            'status' => $status,
            'headers' => $headers,
            'body' => is_string($body) ? $body : (string) json_encode($body),
            'error' => '',
        ];
    }

    protected function sendRequest(string $host, string $apiKey, array $payload): array
    {
        $this->httpRequests[] = ['host' => $host, 'apiKey' => $apiKey, 'payload' => $payload, 'at' => $this->clock];
        $this->clock += $this->requestDuration;

        $response = array_shift($this->httpResponses);
        if ($response === null) {
            throw new \LogicException('No scripted response left');
        }

        return $response;
    }

    protected function now(): float
    {
        return $this->clock;
    }

    protected function pause(float $seconds): void
    {
        $this->pauses[] = round($seconds, 3);
        $this->clock += $seconds;
    }

    protected function isClientGone(): bool
    {
        return $this->clientGone;
    }

    protected function fetchToolCatalog(): array
    {
        $this->catalogFetches++;
        if ($this->catalogError !== null) {
            throw $this->catalogError;
        }

        return $this->catalog;
    }

    protected function callInternalTool(string $name, array $arguments, string $sessionKey): array
    {
        $this->toolCalls[] = [$name, $arguments, $sessionKey];
        $result = array_shift($this->toolResults);
        if ($result instanceof \Throwable) {
            throw $result;
        }

        return $result;
    }

    protected function translate(string $translationKey): string
    {
        return $translationKey;
    }

    protected function getErrorEvent(MistralApiException $exception, EffectiveSettings $settings): array
    {
        if ($this->realErrorEvent) {
            return parent::getErrorEvent($exception, $settings);
        }

        return $exception->getReason() !== null
            ? ['message' => 'notice:' . $exception->getReason() . ':' . $settings->getAgentModel()]
            : ['message' => $exception->getMessage()];
    }

    protected function getSettings(int $idSite): EffectiveSettings
    {
        return $this->settings ?? self::settings();
    }

    protected function isSuperUser(): bool
    {
        return $this->superUser;
    }
}
