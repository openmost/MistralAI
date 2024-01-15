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
        return [
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
        ];
    }

    public function getStylesheetFiles(&$files)
    {
        $files[] = "plugins/MistralAI/assets/css/style.css";
    }

    public function getJavaScriptFiles(&$files)
    {
        $files[] = "plugins/MistralAI/assets/js/script.js";
    }

}
