<?php

namespace Piwik\Plugins\PerformanceAudit\tests\Integration;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\API\Request;
use Piwik\ArchiveProcessor\Rules;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Tests\Framework\Fixture;

/**
 * @group ApiTest
 * @group PerformanceAudit
 * @group Plugins
 */
class ApiTest extends PerformanceAuditIntegrationPreparation
{
    /**
     * @var Date
     */
    private $date;

    public function setUp(): void
    {
        parent::setUp();

        $this->date = Date::factory('2020-06-15');

        Fixture::loadAllTranslations();
        Rules::setBrowserTriggerArchiving(true);

        $this->markTestSkipped('Plugin API integration test contains SQL error and must be revisited: https://github.com/DevDavido/performance-audit-plugin/issues/47');
    }

    public function tearDown(): void
    {
        Fixture::resetTranslations();
        parent::tearDown();
    }

    // Site 1
    public function test_site1_score_day()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 1,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1ScoreRow($response->getFirstRow());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 1,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1ScoreRow($response->getFirstRow());
    }

    public function test_site1_score_week_month_year()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 1,
            'period' => 'week',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1ScoreRow($response->getFirstRow());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 1,
            'period' => 'week',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1ScoreRow($response->getFirstRow());

        // Why SQL exception on log_link_visit_action?
        $this->expectException(Exception::class);
        Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 1,
            'period' => 'month',
            'date' => $this->date->toString('Y-m-d')
        ]);
        Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 1,
            'period' => 'year',
            'date' => $this->date->toString('Y-m-d')
        ]);
        Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 1,
            'period' => 'month',
            'date' => $this->date->toString('Y-m-d')
        ]);
        Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 1,
            'period' => 'year',
            'date' => $this->date->toString('Y-m-d')
        ]);
    }

    public function test_site1_score_non_existent_data()
    {
        foreach (['day', 'week', 'month', 'year'] as $period) {
            $this->assertEquals(0, Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
                'idSite' => 1,
                'period' => $period,
                'date' => $this->date->subYear(10)->toString('Y-m-d')
            ])->getRowsCount());
            $this->assertEquals(0, Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
                'idSite' => 1,
                'period' => $period,
                'date' => $this->date->subYear(10)->toString('Y-m-d')
            ])->getRowsCount());
        }
    }

    public function test_site1_score_range()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 1,
            'period' => 'range',
            'date' => implode(',', [$this->date->toString('Y-m-d'), $this->date->addDay(14)->toString('Y-m-d')])
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1ScoreRow($response->getFirstRow());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 1,
            'period' => 'range',
            'date' => implode(',', [$this->date->toString('Y-m-d'), $this->date->addDay(14)->toString('Y-m-d')])
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1ScoreRow($response->getFirstRow());
    }

    private function assertSite1ScoreRow($row)
    {
        $this->assertEquals('/some/test/page', $row->getColumn('label'));
        $this->assertEquals('50', $row->getColumn('min'));
        $this->assertEquals('80', $row->getColumn('median'));
        $this->assertEquals('100', $row->getColumn('max'));
        $this->assertEquals('https://example.com/some/test/page', $row->getMetadata('url'));
        $this->assertEquals("50% is classified as MODERATE! <br />\n Values between 50% – 90% are in this classification group.", $row->getMetadata('min_tooltip'));
        $this->assertEquals("80% is classified as MODERATE! <br />\n Values between 50% – 90% are in this classification group.", $row->getMetadata('median_tooltip'));
        $this->assertEquals("100% is classified as FAST! <br />\n Values between 90% – 100% are in this classification group.", $row->getMetadata('max_tooltip'));
    }

    public function test_site1_first_contentful_paint_day()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceFirstContentfulPaintMobile', [
            'idSite' => 1,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1FirstContentfulPaintRow($response->getFirstRow());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceFirstContentfulPaintDesktop', [
            'idSite' => 1,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite1FirstContentfulPaintRow($response->getFirstRow());
    }

    private function assertSite1FirstContentfulPaintRow($row)
    {
        $this->assertEquals('/some/test/page', $row->getColumn('label'));
        $this->assertEquals('0.2', $row->getColumn('min'));
        $this->assertEquals('0.4', $row->getColumn('median'));
        $this->assertEquals('0.8', $row->getColumn('max'));
        $this->assertEquals('https://example.com/some/test/page', $row->getMetadata('url'));
        $this->assertEquals("0.2s is classified as FAST! <br />\n Values between 0s – 2s are in this classification group.", $row->getMetadata('min_tooltip'));
        $this->assertEquals("0.4s is classified as FAST! <br />\n Values between 0s – 2s are in this classification group.", $row->getMetadata('median_tooltip'));
        $this->assertEquals("0.8s is classified as FAST! <br />\n Values between 0s – 2s are in this classification group.", $row->getMetadata('max_tooltip'));
    }

    // Site 2
    public function test_site2_score_day()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 2,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(0, $response->getRowsCount());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 2,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite2ScoreRow($response->getFirstRow());
    }

    public function test_site2_score_week_month_year()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 2,
            'period' => 'week',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(0, $response->getRowsCount());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 2,
            'period' => 'week',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite2ScoreRow($response->getFirstRow());

        // Why SQL exception on log_link_visit_action?
        $this->expectException(Exception::class);
        Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 2,
            'period' => 'month',
            'date' => $this->date->toString('Y-m-d')
        ]);
        Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 2,
            'period' => 'year',
            'date' => $this->date->toString('Y-m-d')
        ]);
        Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 2,
            'period' => 'month',
            'date' => $this->date->toString('Y-m-d')
        ]);
        Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 2,
            'period' => 'year',
            'date' => $this->date->toString('Y-m-d')
        ]);
    }

    public function test_site2_score_non_existent_data()
    {
        foreach (['day', 'week', 'month', 'year'] as $period) {
            $this->assertEquals(0, Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
                'idSite' => 2,
                'period' => $period,
                'date' => $this->date->subYear(10)->toString('Y-m-d')
            ])->getRowsCount());
            $this->assertEquals(0, Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
                'idSite' => 2,
                'period' => $period,
                'date' => $this->date->subYear(10)->toString('Y-m-d')
            ])->getRowsCount());
        }
    }

    public function test_site2_score_range()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreMobile', [
            'idSite' => 2,
            'period' => 'range',
            'date' => implode(',', [$this->date->toString('Y-m-d'), $this->date->addDay(14)->toString('Y-m-d')])
        ]);

        $this->assertEquals(0, $response->getRowsCount());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceScoreDesktop', [
            'idSite' => 2,
            'period' => 'range',
            'date' => implode(',', [$this->date->toString('Y-m-d'), $this->date->addDay(14)->toString('Y-m-d')])
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite2ScoreRow($response->getFirstRow());
    }

    private function assertSite2ScoreRow($row)
    {
        $this->assertEquals('/some/test/page', $row->getColumn('label'));
        $this->assertEquals('50', $row->getColumn('min'));
        $this->assertEquals('80', $row->getColumn('median'));
        $this->assertEquals('100', $row->getColumn('max'));
        $this->assertEquals('https://example.org/some/test/page', $row->getMetadata('url'));
        $this->assertEquals("50% is classified as MODERATE! <br />\n Values between 50% – 90% are in this classification group.", $row->getMetadata('min_tooltip'));
        $this->assertEquals("80% is classified as MODERATE! <br />\n Values between 50% – 90% are in this classification group.", $row->getMetadata('median_tooltip'));
        $this->assertEquals("100% is classified as FAST! <br />\n Values between 90% – 100% are in this classification group.", $row->getMetadata('max_tooltip'));
    }

    public function test_site2_first_contentful_paint_day()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceFirstContentfulPaintMobile', [
            'idSite' => 2,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(0, $response->getRowsCount());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceFirstContentfulPaintDesktop', [
            'idSite' => 2,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite2FirstContentfulPaintRow($response->getFirstRow());
    }

    private function assertSite2FirstContentfulPaintRow($row)
    {
        $this->assertEquals('/some/test/page', $row->getColumn('label'));
        $this->assertEquals('2', $row->getColumn('min'));
        $this->assertEquals('4', $row->getColumn('median'));
        $this->assertEquals('8', $row->getColumn('max'));
        $this->assertEquals('https://example.org/some/test/page', $row->getMetadata('url'));
        $this->assertEquals("2s is classified as MODERATE! <br />\n Values between 2s – 4s are in this classification group.", $row->getMetadata('min_tooltip'));
        $this->assertEquals("4s is classified as SLOW! <br />\n Values between 4s – 60s are in this classification group.", $row->getMetadata('median_tooltip'));
        $this->assertEquals("8s is classified as SLOW! <br />\n Values between 4s – 60s are in this classification group.", $row->getMetadata('max_tooltip'));
    }

    public function test_site3_first_contentful_paint_day()
    {
        /** @var DataTable $response */
        // Mobile
        $response = Request::processRequest('PerformanceAudit.getPerformanceFirstContentfulPaintMobile', [
            'idSite' => 3,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(1, $response->getRowsCount());
        $this->assertSite3FirstContentfulPaintRow($response->getFirstRow());

        // Desktop
        $response = Request::processRequest('PerformanceAudit.getPerformanceFirstContentfulPaintDesktop', [
            'idSite' => 3,
            'period' => 'day',
            'date' => $this->date->toString('Y-m-d')
        ]);

        $this->assertEquals(0, $response->getRowsCount());
    }

    private function assertSite3FirstContentfulPaintRow($row)
    {
        $this->assertEquals('www.google.com/some/test/page', $row->getColumn('label'));
        $this->assertEquals('20', $row->getColumn('min'));
        $this->assertEquals('40', $row->getColumn('median'));
        $this->assertEquals('80', $row->getColumn('max'));
        $this->assertEquals('https://www.google.com/some/test/page', $row->getMetadata('url'));
        $this->assertEquals("20s is classified as SLOW! <br />\n Values between 4s – 60s are in this classification group.", $row->getMetadata('min_tooltip'));
        $this->assertEquals("40s is classified as SLOW! <br />\n Values between 4s – 60s are in this classification group.", $row->getMetadata('median_tooltip'));
        $this->assertEquals("80s is out of range of all classification groups.", $row->getMetadata('max_tooltip'));
    }
}
