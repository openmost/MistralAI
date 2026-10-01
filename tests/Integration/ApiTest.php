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
use Piwik\Plugins\MistralAI\Services\InsightReport;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * The insight report data of the classic chat (without agent)
 *
 * @group MistralAI
 * @group MistralAIApiTest
 * @group Plugins
 */
class ApiTest extends IntegrationTestCase
{
    /** @var int */
    private $idSite;

    /** @var array<string, mixed> */
    private $originalGet;

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

    public function test_insightReport_returnsTheReportDataAsJson(): void
    {
        $data = (new InsightReport())->fetch(['module' => 'VisitsSummary', 'action' => 'get'], $this->idSite, 'yesterday', 'day');

        $payload = json_decode($data, true);
        $this->assertSame(['method' => 'VisitsSummary.get', 'idSite' => $this->idSite, 'period' => 'day', 'date' => 'yesterday'], $payload['request']);
        $this->assertArrayHasKey('values', $payload);
        $this->assertArrayHasKey('name', $payload['report']);
    }

    public function test_insightReport_fetchesTheSeriesOfAnEvolutionGraph_withTheSegmentOfTheRequest(): void
    {
        $_GET['segment'] = 'browserCode==FF';

        $data = (new InsightReport())->fetch(
            ['module' => 'VisitsSummary', 'action' => 'getEvolutionGraph', 'forceView' => '1', 'viewDataTable' => 'graphEvolution', 'filter_limit' => '5'],
            $this->idSite,
            '2024-03-31',
            'day'
        );

        $payload = json_decode($data, true);
        $this->assertSame([
            'method' => 'VisitsSummary.get',
            'idSite' => $this->idSite,
            'period' => 'day',
            'date' => '2024-03-02,2024-03-31',
            'segment' => 'browserCode==FF',
        ], $payload['request']);
        $this->assertCount(30, $payload['series']);
    }

    public function test_getInsights_answersWithACleanMessage_whenTheWidgetHasNoReportData(): void
    {
        $result = Request::processRequest('MistralAI.getInsights', [
            'idSite' => $this->idSite,
            'period' => 'day',
            'date' => 'yesterday',
            'widgetParams' => json_encode(['module' => 'SitesManager', 'action' => 'deleteSite']),
        ]);

        $this->assertIsArray($result);
        $message = $result['error']['message'];
        $this->assertNotSame('', $message);
        $this->assertStringNotContainsString('#0 ', $message);
        $this->assertStringNotContainsString('.php', $message);
        $this->assertNotEmpty(Request::processRequest('SitesManager.getSiteFromId', ['idSite' => $this->idSite]));
    }

    public function test_getInsights_answersWithACleanMessage_whenTheReportFails(): void
    {
        $result = Request::processRequest('MistralAI.getInsights', [
            'idSite' => $this->idSite,
            'period' => 'day',
            'date' => 'yesterday',
            'widgetParams' => json_encode(['module' => 'DevicesDetection', 'action' => 'getBrowsers', 'segment' => 'notADimension==1']),
        ]);

        $message = $result['error']['message'];
        $this->assertNotSame('', $message);
        $this->assertStringNotContainsString('#0 ', $message);
        $this->assertSame(0, preg_match('~[A-Za-z]:\\\\|/[\w.-]+/[\w./-]*\.php~', $message));
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
