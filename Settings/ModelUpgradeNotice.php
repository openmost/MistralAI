<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI\Settings;

use Piwik\Piwik;
use Piwik\Plugins\MistralAI\Config;
use Piwik\Url;

/**
 * Message asking to switch to another model, displayed in the chat instead of the raw API error when the model is
 * deprecated, retired or not included in the Mistral AI plan, with a link to the settings the user can change.
 */
final class ModelUpgradeNotice
{
    public const REASON_OUTDATED = 'outdated';
    public const REASON_UNAVAILABLE = 'unavailable';
    public const REASON_NOT_IN_PLAN = 'not_in_plan';
    public const REASON_RATE_LIMITED = 'rate_limited';

    /**
     * Finds whether an API error is caused by the model.
     *
     * @param array<string, string> $headers lowercase response header name => value
     * @return string|null one of the REASON_* constants, null when the error is not related to the model
     */
    public static function classifyApiError(int $httpCode, string $body, array $headers = []): ?string
    {
        $error = json_decode($body, true);
        if (!is_array($error)) {
            $error = [];
        } elseif (isset($error['error']) && is_array($error['error'])) {
            $error = $error['error'];
        }

        $type = is_string($error['type'] ?? null) ? $error['type'] : '';
        $code = is_scalar($error['code'] ?? null) ? (string) $error['code'] : '';
        $message = is_string($error['message'] ?? null) ? $error['message'] : '';

        if (
            $type === 'invalid_model'
            || $code === 'model_not_found'
            || (in_array($httpCode, [400, 404], true) && preg_match('/invalid model|model\b.*\b(not found|does not exist|deprecated|retired)/i', $message))
        ) {
            return self::REASON_UNAVAILABLE;
        }

        if ($type === 'tier_not_allowed') {
            return self::REASON_NOT_IN_PLAN;
        }

        if ($httpCode === 429 || $type === 'rate_limited') {
            // Mistral AI answers "Rate limit exceeded" with a limit of 0 requests per minute for the models the plan
            // does not include, waiting does not help
            if (($headers['x-ratelimit-limit-req-minute'] ?? null) === '0') {
                return self::REASON_NOT_IN_PLAN;
            }

            return self::REASON_RATE_LIMITED;
        }

        return null;
    }

    /**
     * @return array{message: string, settingsUrl: string, settingsLabel: string}|null null when the model is not
     *                                                                                  deprecated
     */
    public static function forOutdatedModel(EffectiveSettings $settings): ?array
    {
        if (!Config::isDeprecatedModel($settings->getModel())) {
            return null;
        }

        return self::build(self::REASON_OUTDATED, $settings);
    }

    /**
     * @param string|null $model the model of the failed request, the chat model by default
     * @param string|null $modelSource where this model is set, an EffectiveSettings::SOURCE_* constant, the source
     *                                 of the chat model by default
     * @return array{message: string, settingsUrl: string, settingsLabel: string}
     */
    public static function build(string $reason, EffectiveSettings $settings, ?string $model = null, ?string $modelSource = null): array
    {
        $model = $model ?? $settings->getModel();
        $recommendedModel = Config::getModelLabel(Config::RECOMMENDED_MODEL);

        switch ($reason) {
            case self::REASON_OUTDATED:
                $message = Piwik::translate('MistralAI_ModelOutdated', [$model, $recommendedModel]);
                break;
            case self::REASON_NOT_IN_PLAN:
                $message = Piwik::translate('MistralAI_ModelNotInPlan', [$model]);
                break;
            case self::REASON_RATE_LIMITED:
                $message = Piwik::translate('MistralAI_ModelRateLimited', [$model]);
                break;
            default:
                $message = Piwik::translate('MistralAI_ModelUnavailable', [$model, $recommendedModel]);
        }

        $settingsUrl = self::getSettingsUrl($settings, $modelSource);
        if ($settingsUrl === '') {
            $message .= ' ' . Piwik::translate('MistralAI_AskAdministratorToChangeModel');
        }

        return [
            'message' => $message,
            'settingsUrl' => $settingsUrl,
            'settingsLabel' => $settingsUrl === '' ? '' : Piwik::translate('MistralAI_ChangeModelInSettings'),
        ];
    }

    /**
     * Settings page where the current user can change the model of the website, empty when the user cannot.
     */
    public static function getSettingsUrl(EffectiveSettings $settings, ?string $modelSource = null): string
    {
        $idSite = $settings->getIdSite();
        $params = ['idSite' => $idSite, 'period' => 'day', 'date' => 'yesterday'];
        $modelSource = $modelSource ?? $settings->getModelSource();

        $isGeneralSetting = in_array($modelSource, [EffectiveSettings::SOURCE_SYSTEM, EffectiveSettings::SOURCE_AGENT], true);
        if ($isGeneralSetting && Piwik::hasUserSuperUserAccess()) {
            return SystemSettingsForm::getUrl($params);
        }

        // a website admin can override the model of the general settings for the website
        if ($modelSource !== EffectiveSettings::SOURCE_AGENT && $idSite > 0 && Piwik::isUserHasAdminAccess($idSite)) {
            return 'index.php?' . Url::getQueryStringFromParameters(['module' => SiteSettingsStorage::PLUGIN_NAME, 'action' => 'manage'] + $params);
        }

        return '';
    }
}
