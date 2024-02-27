<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI;

use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\Validators\NotEmpty;

/**
 * Defines Settings for MistralAI.
 *
 * Usage like this:
 * $settings = new SystemSettings();
 * $settings->metric->getValue();
 * $settings->description->getValue();
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $host;
    public $apiKey;
    public $model;
    public $chatBasePrompt;
    public $insightBasePrompt;

    protected function init()
    {
        // System setting --> allows selection of a single value
        $this->host = $this->createHostSetting();
        $this->apiKey = $this->createApiKeySetting();
        $this->model = $this->createModelSetting();
        $this->chatBasePrompt = $this->createChatBasePromptSetting();
        $this->insightBasePrompt = $this->createInsightBasePromptSetting();
    }

    private function createHostSetting()
    {
        return $this->makeSetting('host', $default = 'https://api.mistral.ai/v1/chat/completions', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Host';
            $field->uiControl = FieldConfig::UI_CONTROL_URL;
            $field->description = 'Change the host to connect your own Mistral instance';
            $field->validators[] = new NotEmpty();
        });
    }

    private function createApiKeySetting()
    {
        return $this->makeSetting('apiKey', $default = null, FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'API Key';
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
            $field->description = 'Add your Mistral AI API Key here';
            $field->validators[] = new NotEmpty();
        });
    }

    private function createModelSetting()
    {
        return $this->makeSetting('model', $default = 'mistral-medium-latest', FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = 'Model';
            $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
            $field->description = 'Select the model you want to use';
            $field->availableValues = array(
                'open-mistral-7b' => 'Mistral 7b',
                'open-mixtral-8x7b' => 'Open Mixtral 8x7b',
                'mistral-tiny' => 'Tiny',
                'mistral-small-latest' => 'Small latest',
                'mistral-medium-latest' => 'Medium latest',
                'mistral-large-latest' => 'Large latest'
            );
            $field->validators[] = new NotEmpty();
        });
    }

    private function createChatBasePromptSetting()
    {
        return $this->makeSetting('chatBasePrompt', $default = 'You are a Matomo expert and know everything about digital analytics. Your answer should be complete and precise.', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Chat base prompt';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
            $field->description = 'Adapt the prompt to get more precise answer in the chat feature';
            $field->validators[] = new NotEmpty();
        });
    }

    private function createInsightBasePromptSetting()
    {
        return $this->makeSetting('insightBasePrompt', $default = 'Give me insights from the dataset formatted in JSON provided below, add bold style to most important metrics of your answer :', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Insight base prompt';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
            $field->description = 'Adapt the prompt to get more precise insights for your reports';
            $field->validators[] = new NotEmpty();
        });
    }
}
