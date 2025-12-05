<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI;

use Piwik\Piwik;
use Piwik\Settings\FieldConfig;

/**
 * Shared settings configuration for MistralAI plugin.
 * Used by both SystemSettings and MeasurableSettings.
 */
trait SettingsBase
{
    /**
     * Configure host field
     */
    protected function configureHostField(FieldConfig $field): void
    {
        $field->title = Piwik::translate('MistralAI_Host');
        $field->uiControl = FieldConfig::UI_CONTROL_URL;
        $field->description = Piwik::translate('MistralAI_HostDescription');
    }

    /**
     * Configure API key field
     */
    protected function configureApiKeyField(FieldConfig $field): void
    {
        $field->title = Piwik::translate('MistralAI_ApiKey');
        $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
        $field->description = Piwik::translate('MistralAI_ApiKeyDescription');
    }

    /**
     * Configure model preset field
     */
    protected function configureModelPresetField(FieldConfig $field, bool $isMeasurable = false): void
    {
        $field->title = Piwik::translate('MistralAI_ModelPreset');
        $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
        $field->description = Piwik::translate($isMeasurable ? 'MistralAI_ModelPresetDescriptionMeasurable' : 'MistralAI_ModelPresetDescription');
        $field->availableValues = $isMeasurable
            ? ['' => Piwik::translate('MistralAI_UseSystemDefault')] + Config::getAvailableModels()
            : Config::getAvailableModels();
    }

    /**
     * Configure model custom field
     */
    protected function configureModelCustomField(FieldConfig $field, bool $isMeasurable = false): void
    {
        $field->title = Piwik::translate('MistralAI_ModelCustom');
        $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        $field->description = Piwik::translate($isMeasurable ? 'MistralAI_ModelCustomDescriptionMeasurable' : 'MistralAI_ModelCustomDescription');
    }

    /**
     * Configure chat base prompt field
     */
    protected function configureChatBasePromptField(FieldConfig $field): void
    {
        $field->title = Piwik::translate('MistralAI_ChatBasePrompt');
        $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
        $field->description = Piwik::translate('MistralAI_ChatBasePromptDescription');
    }

    /**
     * Configure insight base prompt field
     */
    protected function configureInsightBasePromptField(FieldConfig $field): void
    {
        $field->title = Piwik::translate('MistralAI_InsightBasePrompt');
        $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
        $field->description = Piwik::translate('MistralAI_InsightBasePromptDescription');
    }
}
