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
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        );
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'MistralAI_Insights';
        $translationKeys[] = 'MistralAI_Loading';
        $translationKeys[] = 'MistralAI_Submit';
        $translationKeys[] = 'MistralAI_You';
        $translationKeys[] = 'MistralAI_AI';
        $translationKeys[] = 'MistralAI_ErrorMessage';
        $translationKeys[] = 'MistralAI_MessagePlaceholder';
        $translationKeys[] = 'MistralAI_AskQuestion';
        $translationKeys[] = 'MistralAI_InvalidResponse';
        $translationKeys[] = 'MistralAI_AnErrorOccurred';
        $translationKeys[] = 'MistralAI_NoResponseBody';
        $translationKeys[] = 'MistralAI_WaitingForResponse';
    }

    public function getJavaScriptFiles(&$files)
    {
        if ($this->pluginIsConfigured()) {
            $files[] = "plugins/MistralAI/assets/js/app.js";
        }
    }

    public function getStylesheetFiles(&$files)
    {
        if ($this->pluginIsConfigured()) {
            $files[] = "plugins/MistralAI/assets/css/app.css";
        }
    }

    private function pluginIsConfigured(): bool
    {
        // During plugin install/update, settings may not be available
        // Return false gracefully to avoid breaking the update process
        try {
            // Check if SystemSettings class exists and can be instantiated
            if (!class_exists('\\Piwik\\Plugins\\MistralAI\\SystemSettings')) {
                return false;
            }

            $settings = new SystemSettings();

            // Check if settings object is valid and properties are initialized
            if (!is_object($settings)) {
                return false;
            }

            // Check if host property exists and is a Setting instance
            if (!isset($settings->host) || !$settings->host instanceof \Piwik\Settings\Setting) {
                return false;
            }

            // Check if apiKey property exists and is a Setting instance
            if (!isset($settings->apiKey) || !$settings->apiKey instanceof \Piwik\Settings\Setting) {
                return false;
            }

            $host = $settings->host->getValue();
            $apiKey = $settings->apiKey->getValue();

            if (empty($host)) {
                return false;
            }

            // Custom host doesn't require API key
            $isCustomHost = $host !== Config::DEFAULT_HOST;
            if (!$isCustomHost && empty($apiKey)) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // Catch any error during plugin installation/update/initialization
            return false;
        }
    }
}
