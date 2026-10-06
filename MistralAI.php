<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI;

use Piwik\Plugins\MistralAI\Settings\SiteSettingsStorage;

class MistralAI extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return array(
            'Template.afterEventsReport' => 'renderOpenmostCommunicationAfterEvents',
            'Widget.filterWidgets' => 'addOpenmostCommunicationWidgets',
            'Template.beforeContent' => 'renderOpenmostCommunication',
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
        $translationKeys[] = 'MistralAI_SiteSettingsTitle';
        $translationKeys[] = 'MistralAI_SiteSettingsIntro';
        $translationKeys[] = 'MistralAI_SiteSettingsGeneralSettings';
        $translationKeys[] = 'General_GeneralSettings';
        $translationKeys[] = 'General_YourChangesHaveBeenSaved';
        $translationKeys[] = 'MistralAI_AgentToolStep';
        $translationKeys[] = 'MistralAI_AgentMcpUnavailable';
        $translationKeys[] = 'MistralAI_AskAdministrator';
        $translationKeys[] = 'MistralAI_SystemSettingsMenu';
        $translationKeys[] = 'MistralAI_SystemSettingsIntro';
        $translationKeys[] = 'MistralAI_SystemSettingsLink';
        $translationKeys[] = 'MistralAI_SettingsConnectionTitle';
        $translationKeys[] = 'MistralAI_SettingsPrivacyTitle';
        $translationKeys[] = 'MistralAI_PrivacySettingsIntro';
        $translationKeys[] = 'MistralAI_SettingsPromptsTitle';
        $translationKeys[] = 'MistralAI_ResetPromptToDefault';
        $translationKeys[] = 'MistralAI_ResetPromptToDefaultHelp';
        $translationKeys[] = 'MistralAI_UseGeneralPrompt';
        $translationKeys[] = 'MistralAI_UseGeneralPromptHelp';
        $translationKeys[] = 'MistralAI_SiteSettingsPromptsIntro';
        $translationKeys[] = 'MistralAI_DeleteApiKey';
        $translationKeys[] = 'MistralAI_DeleteApiKeyConfirmTitle';
        $translationKeys[] = 'MistralAI_DeleteApiKeyConfirmText';
        $translationKeys[] = 'MistralAI_DeleteSiteApiKeyConfirmText';
        $translationKeys[] = 'MistralAI_DeleteApiKeyDone';
        $translationKeys[] = 'General_Yes';
        $translationKeys[] = 'General_No';
        $translationKeys[] = 'MistralAI_CloseInsights';
        $translationKeys[] = 'MistralAI_CopyAnswer';
        $translationKeys[] = 'MistralAI_AnswerCopied';
        $translationKeys[] = 'MistralAI_ScrollToLatest';
        $translationKeys[] = 'MistralAI_ComposerHint';
        $translationKeys[] = 'MistralAI_AnswerAnnouncement';
        $translationKeys[] = 'MistralAI_AgentStepsSummary';
        $translationKeys[] = 'MistralAI_AgentStepsFailed';
        $translationKeys[] = 'MistralAI_AgentStepRunning';
        $translationKeys[] = 'MistralAI_AgentStepDone';
        $translationKeys[] = 'MistralAI_AgentStepError';
        $translationKeys[] = 'MistralAI_EmptyStateTitle';
        $translationKeys[] = 'MistralAI_EmptyStateText';
        $translationKeys[] = 'MistralAI_DataNotice';
        $translationKeys[] = 'MistralAI_SuggestionsLabel';
        $translationKeys[] = 'MistralAI_SuggestionWeeklyKpis';
        $translationKeys[] = 'MistralAI_SuggestionTopPages';
        $translationKeys[] = 'MistralAI_SuggestionTrafficSources';
        $translationKeys[] = 'MistralAI_SuggestionGoals';
        $translationKeys[] = 'MistralAI_NewConversation';
        $translationKeys[] = 'MistralAI_ScrollableTable';
        $translationKeys[] = 'MistralAI_ScrollableCode';
        $translationKeys[] = 'MistralAI_CopyCode';
        $translationKeys[] = 'MistralAI_CodeCopied';
        foreach (['InstallMcpServer', 'ActivateMcpServer', 'EnableMcp', 'EnableWriteMode'] as $step) {
            $translationKeys[] = 'MistralAI_Recommend' . $step;
            $translationKeys[] = 'MistralAI_Recommend' . $step . 'Action';
        }
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
        // a custom host may have no key: general settings or the key or custom host of a website
        return $this->chatIsConfigured() || SiteSettingsStorage::hasAnySiteConnection();
    }

    private function chatIsConfigured(): bool
    {
        try {
            $settings = new SystemSettings();

            $host = trim((string) $settings->host->getValue());
            $apiKey = trim((string) $settings->apiKey->getValue());

            return $host !== '' && ($apiKey !== '' || !Config::isDefaultHost($host));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function renderOpenmostCommunication(&$out, $layout, $module = '', $action = '')
    {
        OpenmostCommunication::beforeContent($out, (string) $layout, (string) $module, (string) $action, $this->getPluginName());
    }

    public function addOpenmostCommunicationWidgets($list)
    {
        OpenmostCommunication::filterWidgets($list, $this->getPluginName());
    }

    public function renderOpenmostCommunicationAfterEvents(&$out, $dataTable = null)
    {
        OpenmostCommunication::afterEventsReport($out, $this->getPluginName());
    }
}
