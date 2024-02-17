<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI;

class MistralAI extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return array(
            'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
        );
    }

    public function getJavaScriptFiles(&$files)
    {
        if($this->pluginIsConfigured()) {
            $files[] = "plugins/MistralAI/assets/js/app.js";
        }
    }

    public function getStylesheetFiles(&$files)
    {
        if($this->pluginIsConfigured()){
            $files[] = "plugins/MistralAI/assets/css/app.css";
        }
    }

    private function pluginIsConfigured(){
        $settings = new \Piwik\Plugins\MistralAI\SystemSettings();
        $host = $settings->host->getValue();
        $apiKey = $settings->apiKey->getValue();
        $model = $settings->model->getValue();

        return $host && $apiKey && $model;
    }
}
