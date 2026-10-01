<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI\Agent;

use Piwik\Plugin\Manager as PluginManager;

/**
 * Detects the McpServer plugin (Marketplace) the agent mode runs on. Any failure while detecting it means "not
 * available": the plugin must keep working without it.
 *
 * AIProviders is never detected: it has no Mistral AI provider, and the credential of another provider must not be
 * sent to Mistral AI.
 */
class PluginDependencies
{
    public const MCP_SERVER = 'McpServer';

    public const PLUGIN_MISSING = 'missing';
    public const PLUGIN_INACTIVE = 'inactive';
    public const PLUGIN_ACTIVE = 'active';

    public function getPluginState(string $pluginName): string
    {
        try {
            if (!$this->isPluginInFilesystem($pluginName)) {
                return self::PLUGIN_MISSING;
            }

            return $this->isPluginActivated($pluginName) ? self::PLUGIN_ACTIVE : self::PLUGIN_INACTIVE;
        } catch (\Throwable $e) {
            return self::PLUGIN_MISSING;
        }
    }

    protected function isPluginInFilesystem(string $pluginName): bool
    {
        return (bool) PluginManager::getInstance()->isPluginInFilesystem($pluginName);
    }

    protected function isPluginActivated(string $pluginName): bool
    {
        return (bool) PluginManager::getInstance()->isPluginActivated($pluginName);
    }
}
