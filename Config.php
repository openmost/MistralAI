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

    /**
     * Model option resolved to RECOMMENDED_MODEL when a request is sent, so the installs using it follow the
     * recommendation of each plugin release without changing their settings.
     */
    public const LATEST_RECOMMENDED_MODEL = 'latest-recommended';

    public const RECOMMENDED_MODEL = 'ministral-14b-latest';

    public const DEFAULT_MODEL = self::LATEST_RECOMMENDED_MODEL;

    /**
     * Model of the agent mode when its setting is the latest recommended option. Calling the Matomo tools needs a
     * larger model than chatting: with the smaller models, about one agent turn in three repeats invalid tool calls
     * until the agent stops.
     */
    public const RECOMMENDED_AGENT_MODEL = 'ministral-14b-latest';

    /**
     * Agent model option that uses the model of the chat.
     */
    public const AGENT_MODEL_SAME_AS_CHAT = 'same-as-chat';

    public const DEFAULT_AGENT_MODEL = self::LATEST_RECOMMENDED_MODEL;

    /**
     * Returns the list of available preset models suited for chat/conversation.
     *
     * The list only contains models tuned for back-and-forth discussion of
     * report data. Code-completion specialists (Codestral, Devstral), audio
     * (Voxtral), OCR and moderation models are intentionally excluded
     * because they target one-shot tasks rather than conversation.
     */
    public static function getAvailableModels(): array
    {
        return [
            // Generalist
            'mistral-medium-latest' => 'Mistral Medium 3.5',
            'mistral-large-latest' => 'Mistral Large 3',
            'mistral-small-latest' => 'Mistral Small 4',

            // Ministral 3 series (lightweight chat)
            'ministral-14b-latest' => 'Ministral 3 14B',
            'ministral-8b-latest' => 'Ministral 3 8B',
            'ministral-3b-latest' => 'Ministral 3 3B',
        ];
    }

    /**
     * Models deprecated or retired by Mistral AI, or removed from the preset list of a previous plugin version.
     *
     * @return string[]
     */
    public static function getDeprecatedModels(): array
    {
        return [
            'magistral-medium-latest',
            'magistral-small-latest',
            'magistral-medium-2509',
            'magistral-medium-2507',
            'magistral-medium-2506',
            'magistral-small-2509',
            'magistral-small-2507',
            'magistral-small-2506',
            'open-mistral-nemo',
            'open-mistral-nemo-2407',
            'mistral-medium-2508',
            'mistral-medium-2505',
            'mistral-medium-2312',
            'mistral-small-2506',
            'mistral-small-2503',
            'mistral-small-2501',
            'mistral-small-2409',
            'mistral-small-2402',
            'mistral-large-2411',
            'mistral-large-2407',
            'mistral-large-2402',
            'ministral-8b-2410',
            'ministral-3b-2410',
            'pixtral-large-2411',
            'pixtral-12b-2409',
            'mistral-saba-2502',
            'open-mistral-7b',
            'open-mixtral-8x7b',
            'open-mixtral-8x22b',
            'labs-mistral-small-creative',
        ];
    }

    public static function isAvailableModel(string $model): bool
    {
        return $model === self::LATEST_RECOMMENDED_MODEL || array_key_exists($model, self::getAvailableModels());
    }

    public static function isDeprecatedModel(string $model): bool
    {
        return in_array(strtolower(trim($model)), self::getDeprecatedModels(), true);
    }

    /**
     * Model sent to the API for a configured value.
     */
    public static function resolveModel(string $model): string
    {
        $model = trim($model);

        return ($model === '' || $model === self::LATEST_RECOMMENDED_MODEL) ? self::RECOMMENDED_MODEL : $model;
    }

    /**
     * Model sent to the API by the agent mode.
     *
     * @param string $agentModel value of the agent model setting: latest recommended, same as chat, or a model
     * @param string $chatModel model of the chat, used with the "same as chat" option
     */
    public static function resolveAgentModel(string $agentModel, string $chatModel = ''): string
    {
        $agentModel = trim($agentModel);
        if ($agentModel === '' || $agentModel === self::LATEST_RECOMMENDED_MODEL) {
            return self::RECOMMENDED_AGENT_MODEL;
        }

        $model = $agentModel === self::AGENT_MODEL_SAME_AS_CHAT ? self::resolveModel($chatModel) : $agentModel;

        // the 3B models repeat invalid tool calls until the agent stops
        return strpos(strtolower($model), 'ministral-3b') === 0 ? self::RECOMMENDED_AGENT_MODEL : $model;
    }

    public static function getModelLabel(string $model): string
    {
        return self::getAvailableModels()[$model] ?? $model;
    }
}
