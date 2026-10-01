<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Log\LoggerInterface;
use Piwik\Plugins\MistralAI\Agent\McpAgent;
use Piwik\Plugins\MistralAI\Settings\ModelUpgradeNotice;
use Piwik\Plugins\MistralAI\tests\Fakes\FakePluginDependencies;
use Piwik\Plugins\MistralAI\tests\Fakes\ScriptedMcpAgent;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group MistralAI
 * @group MistralAIMcpAgentTest
 * @group Plugins
 */
class McpAgentTest extends IntegrationTestCase
{
    /** @var int */
    private $idSite;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        FakeAccess::clearAccess(true);
        $this->idSite = (int) Fixture::createWebsite('2024-01-01 00:00:00', 1, 'Openmost website');
    }

    public function test_getStatus_fallsBackToTheClassicChat_whenNoKeyIsConfigured(): void
    {
        $status = $this->getAgent()->getStatus($this->idSite);

        $this->assertSame(McpAgent::MODE_CHAT, $status['mode']);
        $this->assertSame('none', $status['keySource']);
        $this->assertContains($status['mcp'], [
            McpAgent::STATUS_READY,
            McpAgent::STATUS_NOT_INSTALLED,
            McpAgent::STATUS_NOT_ACTIVATED,
            McpAgent::STATUS_DISABLED,
            McpAgent::STATUS_NO_ACCESS,
            McpAgent::STATUS_UNAVAILABLE,
        ]);
        $this->assertIsInt($status['toolCount']);
        $this->assertIsBool($status['canPerformActions']);
        $this->assertSame([], $status['recommendations']);
        $this->assertNotSame('', $status['model']);
    }

    public function test_buildSystemPrompt_describesTheContextAndTheExpectedFormat(): void
    {
        $prompt = $this->getAgent()->buildSystemPrompt('  You are a Matomo expert.  ', $this->idSite, 'week', '2026-09-14');

        $this->assertStringStartsWith("You are a Matomo expert.\n", $prompt);
        $this->assertStringContainsString('Matomo tools (MCP)', $prompt);
        $this->assertStringContainsString('website "Openmost website" (idSite ' . $this->idSite . '), period "week", date "2026-09-14"', $prompt);
        $this->assertStringContainsString('never as raw JSON', $prompt);
        $this->assertStringNotContainsString('report the user is currently looking at', $prompt);
    }

    public function test_buildSystemPrompt_doesNotMentionTheTools_whenMcpServerIsUnavailable(): void
    {
        $prompt = $this->getAgent()->buildSystemPrompt('Chat prompt', $this->idSite, 'day', 'yesterday', null, false);

        $this->assertStringStartsWith("Chat prompt\n", $prompt);
        $this->assertStringNotContainsString('MCP', $prompt);
        $this->assertStringContainsString('(idSite ' . $this->idSite . ')', $prompt);

        $insightPrompt = $this->getAgent()->buildSystemPrompt('Insights prompt', $this->idSite, 'day', 'yesterday', '{"nb_visits":12}', false);
        $this->assertStringNotContainsString('tools', $insightPrompt);
        $this->assertStringEndsWith("\n{\"nb_visits\":12}", $insightPrompt);
    }

    public function test_buildSystemPrompt_appendsTheReportData_forInsights(): void
    {
        $prompt = $this->getAgent()->buildSystemPrompt('Insights prompt', $this->idSite, 'day', 'yesterday', '{"nb_visits":12}');

        $this->assertStringContainsString('only use the tools when you need additional data', $prompt);
        $this->assertStringEndsWith("\n{\"nb_visits\":12}", $prompt);
    }

    public function test_buildSystemPrompt_pointsTheModelToTheRequestDescriptor_whenItHasTools(): void
    {
        $payload = '{"request":{"method":"Events.getName","idSite":1,"period":"day","date":"yesterday","segment":"browserCode==CH"},"rows":[]}';

        $prompt = $this->getAgent()->buildSystemPrompt('Insights prompt', $this->idSite, 'day', 'yesterday', $payload);
        $this->assertStringContainsString('"request" object is the exact Matomo API request behind this data', $prompt);
        $this->assertStringContainsString('call the Matomo tools with exactly these parameters', $prompt);
        $this->assertStringContainsString('"segment":"browserCode==CH"', $prompt);

        $withoutTools = $this->getAgent()->buildSystemPrompt('Insights prompt', $this->idSite, 'day', 'yesterday', $payload, false);
        $this->assertStringNotContainsString('call the Matomo tools', $withoutTools);
        $this->assertStringEndsWith("\n" . $payload, $withoutTools);
    }

    public function test_buildSystemPrompt_requiresAConfirmationBeforeAnyChange_whenWriteToolsAreAvailable(): void
    {
        $agent = $this->getScriptedAgent([
            ['name' => 'get_report', 'readOnly' => true],
            ['name' => 'create_goal', 'readOnly' => false],
        ]);

        $prompt = $agent->buildSystemPrompt('Custom prompt without any safety rule', $this->idSite, 'day', 'yesterday');

        $this->assertStringContainsString(McpAgent::CONFIRMATION_RULE, $prompt);
        $this->assertStringContainsString('explicitly', $prompt);

        $insightPrompt = $agent->buildSystemPrompt('Insights prompt', $this->idSite, 'day', 'yesterday', '{"nb_visits":12}');
        $this->assertStringContainsString(McpAgent::CONFIRMATION_RULE, $insightPrompt);
        $this->assertStringEndsWith("\n{\"nb_visits\":12}", $insightPrompt);
    }

    public function test_buildSystemPrompt_omitsTheConfirmation_withoutTools_orWithReadOnlyTools(): void
    {
        $writeAgent = $this->getScriptedAgent([['name' => 'create_goal', 'readOnly' => false]]);
        $withoutTools = $writeAgent->buildSystemPrompt('Chat prompt', $this->idSite, 'day', 'yesterday', null, false);
        $this->assertStringNotContainsString(McpAgent::CONFIRMATION_RULE, $withoutTools);

        $readOnlyAgent = $this->getScriptedAgent([['name' => 'get_report', 'readOnly' => true]]);
        $readOnly = $readOnlyAgent->buildSystemPrompt('Chat prompt', $this->idSite, 'day', 'yesterday');
        $this->assertStringContainsString('MCP', $readOnly);
        $this->assertStringNotContainsString(McpAgent::CONFIRMATION_RULE, $readOnly);
    }

    public function test_run_endsWithATranslatedNoticeAndASettingsLink_whenTheRateLimitPersists(): void
    {
        $agent = new ScriptedMcpAgent(StaticContainer::get(LoggerInterface::class), FakePluginDependencies::withMcpServer());
        $agent->realErrorEvent = true;
        for ($i = 0; $i <= McpAgent::MAX_RETRIES; $i++) {
            $agent->httpResponses[] = ScriptedMcpAgent::http(429, ['message' => 'Rate limit exceeded', 'type' => 'rate_limited']);
        }
        $settings = ScriptedMcpAgent::settings();
        $events = [];

        $agent->run([['role' => 'user', 'content' => 'Hi']], 'Prompt', $settings, 'session', function (string $type, array $data) use (&$events) {
            $events[] = [$type, $data];
        });

        $this->assertCount(1, $events);
        [$type, $error] = $events[0];
        $this->assertSame('error', $type);
        $this->assertSame(ModelUpgradeNotice::build(ModelUpgradeNotice::REASON_RATE_LIMITED, $settings, 'ministral-8b-latest'), $error);
        $this->assertStringStartsWith('MistralAI_ModelRateLimited', $error['message']);
        $this->assertStringContainsString('module=MistralAI&action=settings', $error['settingsUrl']);
        $this->assertNotSame('', $error['settingsLabel']);
    }

    public function test_theAgentModelErrors_linkToTheGeneralSettings_forSuperUsersOnly(): void
    {
        $settings = ScriptedMcpAgent::settings([], ['agentModel' => 'mistral-large-latest']);
        $superUserNotice = ModelUpgradeNotice::build(ModelUpgradeNotice::REASON_NOT_IN_PLAN, $settings, 'mistral-large-latest', $settings->getAgentModelSource());
        $this->assertStringContainsString('module=MistralAI&action=settings', $superUserNotice['settingsUrl']);

        FakeAccess::clearAccess(false, [], [], 'website_admin');
        FakeAccess::setIdSitesAdmin([$settings->getIdSite()]);

        // a website admin can change the chat model of the website, not the agent model of the general settings
        $agentNotice = ModelUpgradeNotice::build(ModelUpgradeNotice::REASON_NOT_IN_PLAN, $settings, 'mistral-large-latest', $settings->getAgentModelSource());
        $chatNotice = ModelUpgradeNotice::build(ModelUpgradeNotice::REASON_NOT_IN_PLAN, $settings);
        $this->assertSame('', $agentNotice['settingsUrl']);
        $this->assertSame('', $agentNotice['settingsLabel']);
        $this->assertStringContainsString('module=MistralAI&action=manage', $chatNotice['settingsUrl']);
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }

    private function getAgent(): McpAgent
    {
        return StaticContainer::get(McpAgent::class);
    }

    /**
     * @param list<array<string, mixed>> $catalog
     */
    private function getScriptedAgent(array $catalog): ScriptedMcpAgent
    {
        $agent = new ScriptedMcpAgent(StaticContainer::get(LoggerInterface::class), FakePluginDependencies::withMcpServer());
        $agent->catalog = $catalog;

        return $agent;
    }
}
