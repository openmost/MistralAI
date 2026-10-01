<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI\Agent;

/**
 * Failed request of the agent to the Mistral AI API
 */
class MistralApiException extends \RuntimeException
{
    /** @var int */
    private $httpCode;

    /** @var string|null */
    private $reason;

    /**
     * @param string|null $reason a ModelUpgradeNotice::REASON_* constant when the model causes the error
     */
    public function __construct(string $message, int $httpCode = 0, ?string $reason = null)
    {
        parent::__construct($message, $httpCode);
        $this->httpCode = $httpCode;
        $this->reason = $reason;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
