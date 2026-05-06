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
     * Returns the list of available preset models suited for chat/conversation.
     *
     * The list only contains models tuned for back-and-forth discussion of
     * report data. Code-completion specialists (Codestral, Devstral), audio
     * (Voxtral) and vision-only models (Pixtral) are intentionally excluded
     * because they target one-shot tasks rather than conversation.
     */
    public static function getAvailableModels(): array
    {
        return [
            // Frontier generalist (latest)
            'mistral-large-latest' => 'Mistral Large 3',
            'mistral-medium-latest' => 'Mistral Medium 3.5',
            'mistral-small-latest' => 'Mistral Small 4',

            // Reasoning
            'magistral-medium-latest' => 'Magistral Medium 1.2',

            // Ministral series (edge / lightweight chat)
            'ministral-8b-latest' => 'Ministral 8B',
            'ministral-3b-latest' => 'Ministral 3B',

            // Legacy
            'open-mistral-nemo' => 'Open Mistral Nemo',
        ];
    }
}
