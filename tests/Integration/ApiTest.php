<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\tests\Integration;

use Piwik\API\Request;
use Piwik\Plugins\MistralAI\Config;
use Piwik\Plugins\MistralAI\Services\InsightReport;
use Piwik\Plugins\MistralAI\SystemSettings;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group MistralAI
 * @group MistralAIApiTest
 * @group Plugins
 */
class ApiTest extends IntegrationTestCase
{
    private int $idSite;

    /** @var array<string, mixed> */
    private array $originalGet;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        FakeAccess::clearAccess(true);
        $this->idSite = (int) Fixture::createWebsite('2024-01-01 00:00:00');

        // the API methods read the site from the request, as when called over HTTP
        $this->originalGet = $_GET;
        $_GET['idSite'] = (string) $this->idSite;
    }

    public function tearDown(): void
    {
        $_GET = $this->originalGet;

        parent::tearDown();
    }

    public function test_getResponse_failsWithAConfigurationError_whenNoApiKeyIsConfigured(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('MistralAI API key is not configured');

        Request::processRequest('MistralAI.getResponse', [
            'idSite' => $this->idSite,
            'period' => 'day',
            'date' => 'yesterday',
            'messages' => json_encode([['role' => 'user', 'content' => 'Hello']]),
        ]);
    }

    public function test_getInsights_fetchesTheReport_beforeRequiringTheModelConfiguration(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('MistralAI API key is not configured');

        Request::processRequest('MistralAI.getInsights', [
            'idSite' => $this->idSite,
            'period' => 'day',
            'date' => 'yesterday',
            'widgetParams' => json_encode(['module' => 'VisitsSummary', 'action' => 'get']),
        ]);
    }

    public function test_systemSettings_defaultToMistralWithoutApiKey(): void
    {
        $settings = new SystemSettings();

        $this->assertSame('https://api.mistral.ai/v1/chat/completions', $settings->host->getValue());
        $this->assertSame(Config::DEFAULT_MODEL, $settings->modelPreset->getValue());
        $this->assertEmpty($settings->apiKey->getValue());
    }

    public function test_availableModels_containTheDefaultModel(): void
    {
        $this->assertArrayHasKey(Config::DEFAULT_MODEL, Config::getAvailableModels());
    }

    public function test_insightReport_returnsTheReportDataAsJson(): void
    {
        $data = (new InsightReport())->fetch(['module' => 'VisitsSummary', 'action' => 'get'], $this->idSite, 'yesterday', 'day');

        $this->assertIsArray(json_decode($data, true));
    }

    public function test_insightReport_checksTheSiteAccess(): void
    {
        FakeAccess::clearAccess(false, [], [], 'anonymous');

        $this->expectException(\Exception::class);

        (new InsightReport())->fetch(['module' => 'VisitsSummary', 'action' => 'get'], $this->idSite, 'yesterday', 'day');
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}
