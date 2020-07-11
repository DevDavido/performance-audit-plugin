<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\Archive;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\DataTable\Map;
use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin\API as BaseAPI;
use Piwik\Plugins\PerformanceAudit\Columns\Filters\AuditScoreClassifier;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MaxPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MaxSeconds;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MedianPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MedianSeconds;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MinPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MinSeconds;
use Piwik\Site;
use Piwik\UrlHelper;

class API extends BaseAPI
{
    /**
     * Get score for mobile devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceScoreMobile(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_Score_Mobile', $idSite, $period, $date, $segment);
    }

    /**
     * Get score for desktop devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceScoreDesktop(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_Score_Desktop', $idSite, $period, $date, $segment);
    }

    /**
     * Get FirstContentfulPaint for mobile devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceFirstContentfulPaintMobile(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_FirstContentfulPaint_Mobile', $idSite, $period, $date, $segment);
    }

    /**
     * Get FirstContentfulPaint for desktop devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceFirstContentfulPaintDesktop(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_FirstContentfulPaint_Desktop', $idSite, $period, $date, $segment);
    }

    /**
     * Get SpeedIndex for mobile devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceSpeedIndexMobile(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_SpeedIndex_Mobile', $idSite, $period, $date, $segment);
    }

    /**
     * Get SpeedIndex for desktop devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceSpeedIndexDesktop(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_SpeedIndex_Desktop', $idSite, $period, $date, $segment);
    }

    /**
     * Get LargestContentfulPaint for mobile devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceLargestContentfulPaintMobile(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_LargestContentfulPaint_Mobile', $idSite, $period, $date, $segment);
    }

    /**
     * Get LargestContentfulPaint for desktop devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceLargestContentfulPaintDesktop(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_LargestContentfulPaint_Desktop', $idSite, $period, $date, $segment);
    }

    /**
     * Get Interactive for mobile devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceInteractiveMobile(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_Interactive_Mobile', $idSite, $period, $date, $segment);
    }

    /**
     * Get Interactive for desktop devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceInteractiveDesktop(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_Interactive_Desktop', $idSite, $period, $date, $segment);
    }

    /**
     * Get TotalBlockingTime for mobile devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceTotalBlockingTimeMobile(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_TotalBlockingTime_Mobile', $idSite, $period, $date, $segment);
    }

    /**
     * Get TotalBlockingTime for desktop devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceTotalBlockingTimeDesktop(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_TotalBlockingTime_Desktop', $idSite, $period, $date, $segment);
    }

    /**
     * Get CumulativeLayoutShift for mobile devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceCumulativeLayoutShiftMobile(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_CumulativeLayoutShift_Mobile', $idSite, $period, $date, $segment);
    }

    /**
     * Get CumulativeLayoutShift for desktop devices.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    public function getPerformanceCumulativeLayoutShiftDesktop(int $idSite, string $period, string $date, $segment = false)
    {
        return $this->getPerformanceDataTable('PerformanceAudit_Report_CumulativeLayoutShift_Desktop', $idSite, $period, $date, $segment);
    }

    /**
     * General method to retrieve DataTable for given table.
     *
     * @param string $dataTableName
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable|Map
     * @throws Exception
     */
    private function getPerformanceDataTable(string $dataTableName, int $idSite, string $period, string $date, $segment)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $dataTable = Archive::createDataTableFromArchive($dataTableName, $idSite, $period, $date, $segment);
        $this->filterUrlLabel($dataTable, $idSite);

        $hasSecondBasedMetric = !strstr($dataTableName, '_Score_');
        $this->formatMetricValues($dataTable, $hasSecondBasedMetric);
        $this->setMetricTooltips($dataTable, $dataTableName);
        $this->disableTotalsRow($dataTable);

        return $dataTable;
    }

    /**
     * Get label for given URL.
     *
     * @param string $url
     * @param string $domain
     * @return string
     */
    private static function getUrlLabelValue(string $url, string $domain)
    {
        if (!$url) {
            return Piwik::translate('General_NotDefined', Piwik::translate('Actions_ColumnPageURL'));
        }

        $domain = UrlHelper::getHostFromUrl($domain);
        $host = UrlHelper::getHostFromUrl($url);

        $url = UrlHelper::getPathAndQueryFromUrl($url);
        if (mb_substr($url, 0, 1) !== '/') {
            $url = '/' . $url;
        }
        if ($domain !== $host) {
            $url = $host . $url;
        }
        if ($url === '/') {
            $url = '/index';
        }

        return $url;
    }

    /**
     * Set new URL label for each row.
     *
     * @param DataTable|Map $dataTable
     * @param int $idSite
     */
    private function filterUrlLabel($dataTable, int $idSite)
    {
        $siteDomain = Site::getMainUrlFor($idSite);
        $dataTable->filter(function (DataTable $dataTable) use ($siteDomain) {
            foreach ($dataTable->getRows() as $row) {
                $newLabel = $this->getUrlLabelValue($row->getColumn('label'), $siteDomain);
                $row->setColumn('label', $newLabel);
            }
        });
    }

    /**
     * Set new formatted value for each row if metric is based on seconds.
     *
     * @param DataTable|Map $dataTable
     * @param bool $isSecondBasedMetric
     */
    private function formatMetricValues($dataTable, bool $isSecondBasedMetric)
    {
        $formatter = new Formatter();
        $dataTable->filter(function (DataTable $dataTable) use ($formatter, $isSecondBasedMetric) {
            foreach ($dataTable->getRows() as $row) {
                $metrics = ($isSecondBasedMetric) ? [
                    new MinSeconds(),
                    new MedianSeconds(),
                    new MaxSeconds()
                ] : [
                    new MinPercent(),
                    new MedianPercent(),
                    new MaxPercent()
                ];
                foreach ($metrics as $metric) {
                    $columnName = $metric->getName();
                    $newValue = $metric->format($row->getColumn($columnName), $formatter);
                    $row->setColumn($columnName, $newValue);
                }
            }
        });
    }

    /**
     * Set tooltip for all metrics.
     *
     * @param DataTable|Map $dataTable
     * @param string $dataTableName
     * @return void
     */
    private function setMetricTooltips($dataTable, string $dataTableName)
    {
        $dataTable->filter(function ($dataTable) use ($dataTableName) {
            $columns = array_diff_key($dataTable->getColumns(), ['label']);
            foreach ($columns as $column) {
                $dataTable->filter('ColumnCallbackAddMetadata', [$column, $column . '_tooltip', [AuditScoreClassifier::class, 'getTooltip'], [$dataTableName]]);
            }
        });
    }

    /**
     * Disables totals row as it doesn't provide any reasonable results in this case.
     *
     * @param DataTable|Map $dataTable
     * @return void
     * @throws Exception
     */
    private function disableTotalsRow($dataTable)
    {
        if ($dataTable instanceof DataTable && Common::getRequestVar('keep_totals_row', false)) {
            $dataTable->setTotalsRow(new Row([
                'label' => DataTable::LABEL_TOTALS_ROW
            ]));
        }
    }
}
