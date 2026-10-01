<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Log\LoggerInterface;
use Piwik\Plugins\MistralAI\Agent\McpAgent;
use Piwik\Plugins\MistralAI\Agent\PluginDependencies;
use Piwik\Plugins\MistralAI\tests\Fakes\FakePluginDependencies;
use Piwik\Plugins\MistralAI\tests\Fakes\ScriptedMcpAgent;

/**
 * @group MistralAI
 * @group PluginDependenciesTest
 * @group Plugins
 */
class PluginDependenciesTest extends TestCase
{
    /**
     * @dataProvider getPluginStates
     */
    public function test_getPluginState(string $scriptedState, string $expectedState): void
    {
        $dependencies = FakePluginDependencies::withMcpServer($scriptedState);

        $this->assertSame($expectedState, $dependencies->getPluginState(PluginDependencies::MCP_SERVER));
    }

    public function getPluginStates(): array
    {
        return [
            'absent from the filesystem' => [PluginDependencies::PLUGIN_MISSING, PluginDependencies::PLUGIN_MISSING],
            'deactivated' => [PluginDependencies::PLUGIN_INACTIVE, PluginDependencies::PLUGIN_INACTIVE],
            'activated' => [PluginDependencies::PLUGIN_ACTIVE, PluginDependencies::PLUGIN_ACTIVE],
            'plugin manager failure' => [FakePluginDependencies::THROW, PluginDependencies::PLUGIN_MISSING],
        ];
    }

    public function test_anUnknownPlugin_isMissing(): void
    {
        $this->assertSame(PluginDependencies::PLUGIN_MISSING, FakePluginDependencies::withMcpServer()->getPluginState('Unknown'));
    }

    /**
     * @dataProvider getMcpServerSetups
     */
    public function test_theMcpServerSetup_drivesTheAgentStatus(
        string $pluginState,
        array $catalog,
        string $expectedMcp,
        string $expectedMode,
        bool $expectedActions
    ): void {
        $agent = new ScriptedMcpAgent($this->createMock(LoggerInterface::class), FakePluginDependencies::withMcpServer($pluginState));
        $agent->catalog = $catalog;

        $status = $agent->getStatus(1);

        $this->assertSame($expectedMcp, $status['mcp']);
        $this->assertSame($expectedMode, $status['mode']);
        $this->assertSame($expectedActions, $status['canPerformActions']);
        $this->assertSame(count($expectedMcp === McpAgent::STATUS_READY ? $catalog : []), $status['toolCount']);
    }

    public function getMcpServerSetups(): array
    {
        $readOnly = [['name' => 'matomo_site_list', 'readOnly' => true]];
        $writeMode = array_merge($readOnly, [['name' => 'matomo_api_call_create', 'readOnly' => false]]);

        return [
            'absent' => [PluginDependencies::PLUGIN_MISSING, $writeMode, McpAgent::STATUS_NOT_INSTALLED, McpAgent::MODE_CHAT, false],
            'deactivated' => [PluginDependencies::PLUGIN_INACTIVE, $writeMode, McpAgent::STATUS_NOT_ACTIVATED, McpAgent::MODE_CHAT, false],
            'read-only' => [PluginDependencies::PLUGIN_ACTIVE, $readOnly, McpAgent::STATUS_READY, McpAgent::MODE_AGENT, false],
            'write mode' => [PluginDependencies::PLUGIN_ACTIVE, $writeMode, McpAgent::STATUS_READY, McpAgent::MODE_AGENT, true],
            'plugin manager throws' => [FakePluginDependencies::THROW, $writeMode, McpAgent::STATUS_NOT_INSTALLED, McpAgent::MODE_CHAT, false],
        ];
    }

    public function test_theAgent_neverDetectsAiProviders(): void
    {
        $dependencies = new class extends PluginDependencies {
            /** @var list<string> */
            public $checked = [];

            protected function isPluginInFilesystem(string $pluginName): bool
            {
                $this->checked[] = $pluginName;
                return true;
            }

            protected function isPluginActivated(string $pluginName): bool
            {
                return true;
            }
        };
        $agent = new ScriptedMcpAgent($this->createMock(LoggerInterface::class), $dependencies);

        $agent->getStatus(1);

        $this->assertSame([PluginDependencies::MCP_SERVER], array_values(array_unique($dependencies->checked)));
    }
}
