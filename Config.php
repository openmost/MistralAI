<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI;

/**
 * Configuration constants and static data for MistralAI plugin.
 * This class has no dependencies to avoid circular loading issues.
 */
class Config
{
    public const DEFAULT_HOST = 'https://api.mistral.ai/v1/chat/completions';
    public const DEFAULT_MODEL = 'mistral-medium-latest';

    /**
     * Returns the list of available preset models
     */
    public static function getAvailableModels(): array
    {
        return [
            'mistral-large-latest' => 'Mistral Large',
            'mistral-medium-latest' => 'Mistral Medium',
            'mistral-small-latest' => 'Mistral Small',
            'open-mistral-nemo' => 'Open Mistral Nemo',
            'codestral-latest' => 'Codestral',
            'pixtral-large-latest' => 'Pixtral Large',
            'ministral-8b-latest' => 'Ministral 8B',
            'ministral-3b-latest' => 'Ministral 3B',
        ];
    }
}
