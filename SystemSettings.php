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

    protected function init()
    {
        // System setting --> allows selection of a single value
        $this->host = $this->createHostSetting();
        $this->apiKey = $this->createApiKeySetting();
        $this->model = $this->createModelSetting();

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
        return $this->makeSetting('model', $default = 'mistral-small', FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = 'Model';
            $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
            $field->description = 'Select the model you want to use';
            $field->availableValues = array(
                'mistral-tiny' => 'tiny',
                'mistral-small' => 'small',
                'mistral-medium' => 'medium'
            );
            $field->validators[] = new NotEmpty();
        });
    }
}
