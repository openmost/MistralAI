<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI\tests\Fakes;

use Piwik\Plugins\MistralAI\Agent\PluginDependencies;

/**
 * Plugin detection with scripted plugin states, the real detection logic runs on top of it
 */
class FakePluginDependencies extends PluginDependencies
{
    public const THROW = 'throw';

    /** @var array<string, string> plugin name => PLUGIN_* state, or THROW when the plugin manager fails */
    public $plugins = [];

    public static function withMcpServer(string $mcpState = self::PLUGIN_ACTIVE): self
    {
        $dependencies = new self();
        $dependencies->plugins = [self::MCP_SERVER => $mcpState];

        return $dependencies;
    }

    protected function isPluginInFilesystem(string $pluginName): bool
    {
        return $this->getScriptedState($pluginName) !== self::PLUGIN_MISSING;
    }

    protected function isPluginActivated(string $pluginName): bool
    {
        return $this->getScriptedState($pluginName) === self::PLUGIN_ACTIVE;
    }

    private function getScriptedState(string $pluginName): string
    {
        $state = isset($this->plugins[$pluginName]) ? $this->plugins[$pluginName] : self::PLUGIN_MISSING;
        if ($state === self::THROW) {
            throw new \RuntimeException('The plugin manager failed.');
        }

        return $state;
    }
}
