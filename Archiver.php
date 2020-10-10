<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\Common;
use Piwik\Config;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Db;
use Piwik\Log;
use Piwik\Period;
use Piwik\Plugin\Archiver as BaseArchiver;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\Audit;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\Max;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\Median;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\Min;
use Piwik\Tracker\Db\DbException;
use Piwik\Tracker\PageUrl;

/**
 * Class encapsulating logic to process Day/Period Archiving for the Actions reports.
 */
class Archiver extends BaseArchiver
{
    /**
     * The prefix for all database records
     *
     * @var string
     */
    private const DATABASE_RECORD_PREFIX = 'PerformanceAudit_Report_';

    /**
     * Archives performance audit reports for a day.
     *
     * @return bool
     * @throws DbException
     */
    public function aggregateDayReport()
    {
        $this->aggregateReport();

        return true;
    }

    /**
     * Archives performance audit reports for more than a day.
     *
     * @return bool
     * @throws DbException
     */
    public function aggregateMultipleReports()
    {
        $this->aggregateReport();

        return true;
    }

    /**
     * Determine if archiver should run if no new reports are available.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldRunEvenWhenNoVisits()
    {
        $deletedDuplicateCount = self::deleteArchiveDuplicates();
        Log::debug($deletedDuplicateCount . ' archive entries got deleted');

        return true;
    }

    /**
     * Aggregates logs for given time period.
     *
     * @return void
     * @throws DbException|Exception
     */
    private function aggregateReport()
    {
        $params = $this->getProcessor()->getParams();
        $period = $params->getPeriod();
        $idSites = $params->getIdSites();
        $metrics = array_values(Audit::METRICS);
        $emulatedDevices = EmulatedDevice::getList(EmulatedDevice::Both);

        foreach ($idSites as $idSite) {
            Log::info("Will process performance audit for website id = {$idSite}, period = {$period}");
            foreach ($metrics as $metric) {
                foreach ($emulatedDevices as $emulatedDevice) {
                    $table = new DataTable();
                    $table->setMaximumAllowedRows(0);

                    $emulatedDeviceId = EmulatedDevice::getIdFor($emulatedDevice);
                    $rows = $this->fetchAllMetrics($idSite, $metric, $period, $emulatedDeviceId);
                    foreach ($rows as $row) {
                        $url = PageUrl::reconstructNormalizedUrl($row['url'], $row['url_prefix']);
                        $url = Common::unsanitizeInputValue($url);
                        $table->addRowFromArray([
                            Row::COLUMNS => [
                                'label' => $url,
                                (new Min())->getName() => $row['min'],
                                (new Median())->getName() => $row['median'],
                                (new Max())->getName() => $row['max'],
                            ],
                            Row::METADATA => [
                                'url' => $url
                            ]
                        ]);
                    }

                    $recordName = sprintf(
                        self::DATABASE_RECORD_PREFIX . '%s_%s',
                        ucfirst($metric),
                        ucfirst($emulatedDevice)
                    );
                    $this->insertTable($table, $recordName);
                }
            }
        }
    }

    /**
     * Fetch all needed metrics for given period.
     *
     * @param string $idSite
     * @param string $key
     * @param Period $period
     * @param int $emulatedDevice
     * @return array
     * @throws DbException
     */
    private function fetchAllMetrics(string $idSite, string $key, Period $period, int $emulatedDevice)
    {
        $baseParameter = [
            $idSite,
            $period->getDateTimeStart(),
            $period->getDateTimeEnd(),
            $emulatedDevice,
            $key
        ];

        $actionIds = $this->fetchLogPerformanceActionIds($baseParameter);
        if (empty($actionIds)) {
            return [];
        }
        $actionIdCounts = array_count_values(array_column($actionIds, 'idaction'));
        $middleRowIndices = $this->calculateMiddleRowIndices($actionIdCounts);
        $middleRowQueries = [];
        foreach ($middleRowIndices as $actionId => $actionMiddleRowIndices) {
            $middleRowQueries[] = sprintf(
                '(`lp_sub`.`idaction` = %d AND `lp_sub`.`row_number` IN (%s))',
                (int) $actionId,
                implode(',', $actionMiddleRowIndices)
            );
        }
        $whereMiddleRowStatement = implode(' OR ', $middleRowQueries);

        return Db::getReader()->fetchAll('
            SELECT
                `lp_sub`.`name` AS `url`,
                `lp_sub`.`url_prefix` AS `url_prefix`,
                `lp_sub`.`idaction` AS `idaction`,
                MIN(`lp_sub`.`min`) AS `min`,
                ROUND(AVG(`lp_sub`.`median`), 1) AS `median`,
                MAX(`lp_sub`.`max`) AS `max`
            FROM (
                SELECT
                    `la`.`name`,
                    `la`.`url_prefix`,
                    `lp`.`idaction`,
                    `lp`.`min`,
                    `lp`.`median`,
                    `lp`.`max`,
                    @row_number := IF(@previous_value = `lp`.`idaction`, @row_number + 1, 0) AS `row_number`,
                    @previous_value := `lp`.`idaction`
                FROM
                    (SELECT @row_number := 0) AS `rn`,
                    (SELECT @previous_value := -1) AS `pv`,
                    `' . Common::prefixTable('log_performance'). '` AS `lp`
                INNER JOIN `' . Common::prefixTable('log_action'). '` AS `la`
                    ON `lp`.`idaction` = `la`.`idaction`
                WHERE
                    `lp`.`idsite` = ? AND
                    `lp`.`created_at` BETWEEN ? AND ? AND
                    `lp`.`emulated_device` = ? AND
                    `lp`.`key` = ?
                ORDER BY `lp`.`idsite`, `lp`.`median`
            ) AS `lp_sub`
            WHERE ' . $whereMiddleRowStatement . '
            GROUP BY `lp_sub`.`idaction`
        ', $baseParameter);
    }

    /**
     * Insert DataTable into blob database table.
     *
     * @param DataTable $table
     * @param string $recordName
     * @throws Exception
     */
    protected function insertTable(DataTable $table, string $recordName)
    {
        $maxRows = Config::getInstance()->General['datatable_archiving_maximum_rows_actions'];
        $maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_actions'];

        $report = $table->getSerialized($maxRows, $maximumRowsInSubDataTable, (new Median())->getName());
        $this->getProcessor()->insertBlobRecord($recordName, $report);
    }

    /**
     * Get all action IDs for given parameters.
     *
     * @param array $parameter
     * @return array
     * @throws DbException
     */
    private function fetchLogPerformanceActionIds(array $parameter)
    {
        return Db::getReader()->fetchAll('
            SELECT `lp`.`idaction`
            FROM `' . Common::prefixTable('log_performance'). '` AS `lp`
            WHERE
                `lp`.`idsite` = ? AND
                `lp`.`created_at` BETWEEN ? AND ? AND
                `lp`.`emulated_device` = ? AND
                `lp`.`key` = ?
        ', $parameter);
    }

    /**
     * Delete all duplicate entries from archive table.
     *
     * @return int
     * @throws Exception
     */
    private static function deleteArchiveDuplicates()
    {
        // Table name is already prefixed
        $currentTable = ArchiveTableCreator::getBlobTable(Date::factory('now'));
        $archiveDuplicateEntries = Db::getReader()->fetchAll('
            SELECT
                MIN(`idarchive`) AS `idarchive`,
                `name`,
                COUNT(*) AS `duplicates`
            FROM
                `' . $currentTable . '`
            WHERE
                `name` LIKE ?
            GROUP BY
                `name`,
                `idsite`,
                `date1`,
                `date2`,
                `period`,
                `value`
            HAVING
                `duplicates` > 1
        ', [self::DATABASE_RECORD_PREFIX . '%']);

        if (count($archiveDuplicateEntries) < 1) {
            return 0;
        }
        $archiveDuplicateIds = array_column($archiveDuplicateEntries, 'idarchive');
        $archiveDuplicateNames = array_column($archiveDuplicateEntries, 'name');
        $archiveDuplicateIdPlaceholder = rtrim(str_repeat('?,', count($archiveDuplicateIds)), ',');
        $archiveDuplicateNamePlaceholder = rtrim(str_repeat('?,', count($archiveDuplicateNames)), ',');

        return Db::deleteAllRows(
            $currentTable,
            'WHERE `idarchive` IN (' . $archiveDuplicateIdPlaceholder . ') AND `name` IN (' . $archiveDuplicateNamePlaceholder . ')',
            '`idarchive` ASC',
            100000,
            array_merge($archiveDuplicateIds, $archiveDuplicateNames)
        );
    }

    /**
     * Calculate middle rows indices for given counts per array element.
     *
     * @param array $arrayCounts
     * @return array
     */
    private function calculateMiddleRowIndices(array $arrayCounts)
    {
        $middleRowIndices = [];
        foreach ($arrayCounts as $id => $count) {
            $isEven = $count % 2 === 0;
            $middleRowIndices[$id] = ($isEven) ?
                [intval($count / 2) - 1, intval($count / 2)] :
                [intval(floor($count / 2))];
        }

        return $middleRowIndices;
    }
}
