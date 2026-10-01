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
use Piwik\Plugins\MistralAI\Agent\McpAgent;
use Piwik\Plugins\MistralAI\Agent\Recommendations;

/**
 * @group MistralAI
 * @group RecommendationsTest
 * @group Plugins
 */
class RecommendationsTest extends TestCase
{
    private const URL_PARAMS = ['idSite' => 3, 'period' => 'week', 'date' => '2026-09-14'];

    private const PLUGINS_URL = 'index.php?module=CorePluginsAdmin&action=plugins&idSite=3&period=week&date=2026-09-14';
    private const MARKETPLACE_URL = 'index.php?module=Marketplace&action=overview&idSite=3&period=week&date=2026-09-14#?showPlugin=McpServer';
    private const MCP_SETTINGS_URL = 'index.php?module=CoreAdminHome&action=generalSettings&idSite=3&period=week&date=2026-09-14#/McpServer';

    /**
     * @dataProvider getStates
     */
    public function test_build_forSuperUsers(array $state, array $expected): void
    {
        $recommendations = Recommendations::build($state, true, self::URL_PARAMS);

        $this->assertSame($expected, array_map(function (array $recommendation) {
            return [$recommendation['id'], $recommendation['url']];
        }, $recommendations));

        foreach ($recommendations as $recommendation) {
            $this->assertFalse($recommendation['askAdministrator']);
            $this->assertStringStartsWith('MistralAI_', $recommendation['message']);
            $this->assertSame($recommendation['url'] === '', $recommendation['action'] === '');
        }
    }

    /**
     * @dataProvider getStates
     */
    public function test_build_forOtherUsers_asksTheAdministrator_withoutLinks(array $state, array $expected): void
    {
        $recommendations = Recommendations::build($state, false, self::URL_PARAMS);

        $this->assertSame(array_column($expected, 0), array_column($recommendations, 'id'));
        foreach ($recommendations as $recommendation) {
            $this->assertSame('', $recommendation['url']);
            $this->assertSame('', $recommendation['action']);
            $this->assertTrue($recommendation['askAdministrator']);
        }
    }

    public function getStates(): array
    {
        return [
            'MCP Server ready with write mode' => [$this->state(McpAgent::STATUS_READY, true), []],
            'read-only MCP Server' => [
                $this->state(McpAgent::STATUS_READY, false),
                [[Recommendations::ENABLE_WRITE_MODE, self::MCP_SETTINGS_URL]],
            ],
            'MCP Server absent' => [
                $this->state(McpAgent::STATUS_NOT_INSTALLED),
                [[Recommendations::INSTALL_MCP_SERVER, self::MARKETPLACE_URL]],
            ],
            'MCP Server deactivated' => [
                $this->state(McpAgent::STATUS_NOT_ACTIVATED),
                [[Recommendations::ACTIVATE_MCP_SERVER, self::PLUGINS_URL]],
            ],
            'MCP disabled in its settings' => [
                $this->state(McpAgent::STATUS_DISABLED),
                [[Recommendations::ENABLE_MCP, self::MCP_SETTINGS_URL]],
            ],
            'MCP Server failing' => [
                $this->state(McpAgent::STATUS_UNAVAILABLE),
                [[Recommendations::MCP_UNAVAILABLE, '']],
            ],
            'no access to the MCP tools' => [$this->state(McpAgent::STATUS_NO_ACCESS), []],
            'unknown status' => [$this->state('something_else'), []],
        ];
    }

    public function test_build_neverRecommendsAiProviders(): void
    {
        $constants = (new \ReflectionClass(Recommendations::class))->getConstants();

        foreach ($constants as $name => $value) {
            $this->assertStringNotContainsStringIgnoringCase('aiprovider', $name);
            $this->assertStringNotContainsStringIgnoringCase('provider', (string) json_encode($value), $name);
        }
    }

    public function test_build_encodesTheUrlParameters(): void
    {
        $recommendation = Recommendations::build(
            $this->state(McpAgent::STATUS_NOT_ACTIVATED),
            true,
            ['idSite' => '1', 'period' => 'day', 'date' => 'today"><script>&module=x']
        )[0];

        $this->assertSame(
            'index.php?module=CorePluginsAdmin&action=plugins&idSite=1&period=day&date=today%22%3E%3Cscript%3E%26module%3Dx',
            $recommendation['url']
        );
    }

    public function test_build_omitsTheMissingUrlParameters(): void
    {
        $recommendation = Recommendations::build($this->state(McpAgent::STATUS_READY, false), true)[0];

        $this->assertSame('index.php?module=CoreAdminHome&action=generalSettings#/McpServer', $recommendation['url']);
    }

    public function test_build_usesTranslationKeysThatExist(): void
    {
        $translations = json_decode((string) file_get_contents(__DIR__ . '/../../lang/en.json'), true)['MistralAI'];

        foreach (array_column($this->getStates(), 0) as $state) {
            foreach (Recommendations::build($state, true, self::URL_PARAMS) as $recommendation) {
                foreach (['message', 'action'] as $field) {
                    if ($recommendation[$field] === '') {
                        continue;
                    }
                    $key = substr($recommendation[$field], strlen('MistralAI_'));
                    $this->assertArrayHasKey($key, $translations, $recommendation[$field]);
                }
            }
        }
        $this->assertArrayHasKey('AskAdministrator', $translations);
    }

    public function test_theAgentTranslations_existInEveryLanguage(): void
    {
        $keys = [
            'AgentToolStep', 'AgentMaxIterations', 'AgentMcpUnavailable', 'InsightAgentPrompt', 'AskAdministrator',
            'AgentModel', 'AgentModelDescription', 'AgentModelLatestRecommended', 'AgentModelSameAsChat', 'AgentModelNote',
        ];
        foreach (['InstallMcpServer', 'ActivateMcpServer', 'EnableMcp', 'EnableWriteMode'] as $step) {
            $keys[] = 'Recommend' . $step;
            $keys[] = 'Recommend' . $step . 'Action';
        }

        $files = glob(__DIR__ . '/../../lang/*.json');
        $this->assertGreaterThan(10, count($files));
        foreach ($files as $file) {
            $translations = json_decode((string) file_get_contents($file), true)['MistralAI'];
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $translations, basename($file) . ' ' . $key);
                $this->assertNotSame('', trim($translations[$key]), basename($file) . ' ' . $key);
                $this->assertStringNotContainsString("\u{2014}", $translations[$key], basename($file) . ' ' . $key);
            }
            foreach (['AgentToolStep', 'AgentModelDescription', 'AgentModelLatestRecommended'] as $key) {
                $this->assertSame(1, substr_count($translations[$key], '%s'), basename($file) . ' ' . $key);
            }
            $this->assertStringNotContainsString('%', $translations['AgentModelNote'], basename($file));
        }
    }

    private function state(string $mcp, bool $canPerformActions = false): array
    {
        return ['mcp' => $mcp, 'canPerformActions' => $canPerformActions];
    }
}
