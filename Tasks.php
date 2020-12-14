<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use ArrayObject;
use CallbackFilterIterator;
use Exception;
use FilesystemIterator;
use OutOfBoundsException;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\DataTable\Map;
use Piwik\Date;
use Piwik\Db;
use Piwik\Log;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugin\Tasks as BaseTasks;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\Audit;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedAuthoriseRefusedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedMethodNotAllowedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedNotFoundException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedTooManyRequestsException;
use Piwik\Site;
use Piwik\Tracker\Action;
use Piwik\Tracker\Db\DbException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;

class Tasks extends BaseTasks
{
    /**
     * Folder name where audit files will be stored.
     */
    private const AUDIT_FOLDER = 'Audits';

    /**
     * Lighthouse instances indexed by site ID.
     *
     * @var Lighthouse[]
     */
    private static $lighthouse;

    /**
     * Whether tasks are executed in debug mode or not.
     *
     * @var bool
     */
    private $debugMode = false;

    /**
     * Log output for enabled debug mode.
     *
     * @var array
     */
    private $logOutput;

    /**
     * Schedule tasks.
     *
     * @return void
     */
    public function schedule()
    {
        $this->weekly('clearTaskRunningFlag', null, self::HIGH_PRIORITY);

        foreach (Site::getSites() as $site) {
            $this->daily('auditSite', (int) $site['idsite'], self::LOW_PRIORITY);
        }
    }

    /**
     * Clear the task running flag once a week
     * in case of any unexpected abrupt failure
     * where the flag has not been deleted.
     *
     * @return void
     */
    public function clearTaskRunningFlag()
    {
        $this->logDebug('Clear task running flag now');
        Option::delete($this->hasTaskRunningKey());
    }

    /**
     * Runs performance audit for specified site.
     *
     * @param int $idSite
     * @param bool $debug
     * @return void
     * @throws Exception
     */
    public function auditSite(int $idSite, bool $debug = false)
    {
        if ($debug) {
            $this->enableDebug();
            $this->logDebug('Debug mode enabled');
        } else {
            // In case audit was called multiple times simultaneously
            // start at different times by sleeping between 1 and 5 seconds
            // to make sure the audit is only executed once by using flags afterwards
            usleep(random_int(1 * 1000000, 5 * 1000000));
        }

        if ($this->hasAnyTaskRunning() && !$this->isInDebugMode()) {
            $this->logInfo('A Performance Audit task is currently already running');
            return;
        }
        if ($this->hasTaskStartedToday($idSite) && !$this->isInDebugMode()) {
            $this->logInfo('Performance Audit task for site ' . $idSite . ' has been already started today');
            return;
        }

        $siteSettings = new MeasurableSettings($idSite);
        if (!$siteSettings->isAuditEnabled() && !$this->isInDebugMode()) {
            $this->logInfo('Performance Audit task for site ' . $idSite . ' will be skipped due to setting which disables it for this site');
            return;
        }

        $urls = $this->getPageUrls($idSite, 'last30');
        if (empty($urls)) {
            $this->logWarning('Performance Audit task for site ' . $idSite . ' has no URLs to check');
            return;
        }

        $this->setDatabaseTimeoutConfiguration();
        $this->logInfo('Database timeout configuration: ' . json_encode($this->getDatabaseTimeoutConfiguration()));

        $this->logInfo('Performance Audit task for site ' . $idSite . ' will be started now (microtime: ' . microtime() . ')');
        try {
            if (!$this->isInDebugMode()) {
                $this->markTaskAsRunning();
                $this->markTaskAsStartedToday($idSite);
            }

            $runs = $siteSettings->getRuns();
            $emulatedDevices = $siteSettings->getEmulatedDevicesList();

            if ($siteSettings->hasGroupedUrls()) {
                $this->groupUrlsByPath($urls);
            }

            if ($this->isInDebugMode()) {
                $urls = [array_shift($urls)];
                $runs = [1];
            }

            $this->performAudits($idSite, $urls, $emulatedDevices, $runs);
            $auditFileCount = iterator_count($this->getAuditFiles($idSite));
            $this->logDebug('Audit file count: ' . $auditFileCount);
            if ($auditFileCount > 0) {
                $this->storeResultsInDatabase($idSite, $this->processAuditFiles($idSite));
                $this->removeAuditFiles($idSite);
            }
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        } finally {
            if (!$this->isInDebugMode()) {
                $this->markTaskAsFinished();
            }
        }
        $this->runGarbageCollection();
        $this->logInfo('Performance Audit task for site ' . $idSite . ' has finished');
    }

    /**
     * Check if any task is currently running.
     *
     * @return bool
     * @throws Exception
     */
    private function hasAnyTaskRunning()
    {
        Option::clearCachedOption($this->hasTaskRunningKey());
        $hasTaskRunning = !!Option::get($this->hasTaskRunningKey());

        return $hasTaskRunning;
    }

    /**
     * Check if this task has started today.
     *
     * @param int $idSite
     * @return bool
     * @throws Exception
     */
    private function hasTaskStartedToday(int $idSite)
    {
        Option::clearCachedOption($this->lastTaskRunKey($idSite));
        $lastRun = Option::get($this->lastTaskRunKey($idSite));
        if (!$lastRun) {
            return false;
        }

        return Date::factory((int) $lastRun)->isToday();
    }

    /**
     * Marks a task as running in DB.
     *
     * @return void
     * @throws Exception
     */
    private function markTaskAsRunning()
    {
        $this->logDebug('Mark task as running now');
        Option::set($this->hasTaskRunningKey(), 1);
    }

    /**
     * Marks a task as finished in DB by deleting the running option key.
     *
     * @return void
     * @throws Exception
     */
    private function markTaskAsFinished()
    {
        $this->logDebug('Mark task as finished now');
        Option::delete($this->hasTaskRunningKey());
    }

    /**
     * Marks this task as started today in DB.
     *
     * @param int $idSite
     * @return void
     * @throws Exception
     */
    private function markTaskAsStartedToday(int $idSite)
    {
        $this->logDebug('Mark task for site ' . $idSite . ' as started today');
        Option::set($this->lastTaskRunKey($idSite), Date::factory('today')->getTimestamp());
    }

    /**
     * Returns the option name for a currently running task.
     *
     * @return string
     */
    public static function hasTaskRunningKey()
    {
        return 'hasRunningPerformanceAuditTask';
    }

    /**
     * Returns the option name of the option that stores the time for this tasks last execution.
     *
     * @param int $idSite
     * @return string
     */
    public static function lastTaskRunKey($idSite)
    {
        return 'lastRunPerformanceAuditTask_' . $idSite;
    }

    /**
     * Return instance of Lighthouse class (singleton).
     *
     * @param int $idSite
     * @return Lighthouse
     * @throws Exception
     */
    private static function getLighthouse(int $idSite)
    {
        if (!isset(self::$lighthouse[$idSite])) {
            self::$lighthouse[$idSite] = (new Lighthouse())->performance();
            Audit::enableEachLighthouseAudit(self::$lighthouse[$idSite]);

            $siteSettings = new MeasurableSettings($idSite);
            if ($siteSettings->hasExtendedTimeout()) {
                self::$lighthouse[$idSite]->setTimeout(300);
            }
            if ($siteSettings->hasExtraHttpHeader()) {
                self::$lighthouse[$idSite]->setHeaders([
                    $siteSettings->getSetting('extra_http_header_key')->getValue() => $siteSettings->getSetting('extra_http_header_value')->getValue()
                ]);
            }
        }

        return self::$lighthouse[$idSite];
    }

    /**
     * Return all page urls of site with given ID for given date.
     *
     * @param int $idSite
     * @param string $date
     * @return array
     */
    private function getPageUrls(int $idSite, string $date)
    {
        /** @var $dataTables Map */
        $dataTables = Request::processRequest('Actions.getPageUrls', [
            'idSite' => $idSite,
            'date' => $date,
            'period' => 'day',
            'expanded' => 0,
            'depth' => PHP_INT_MAX,
            'flat' => 1,
            'enable_filter_excludelowpop' => 0,
            'include_aggregate_rows' => 0,
            'keep_totals_row' => 0,
            'filter_by' => 'all',
            'filter_offset' => 0,
            'filter_limit' => -1,
            'disable_generic_filters' => 1,
            'format' => 'original'
        ]);

        $urls = [];
        foreach ($dataTables->getDataTables() as $dataTable) {
            foreach ($dataTable->getRows() as $row) {
                $url = $row->getMetadata('url');
                // Push only URLs with HTTP or HTTPS protocol
                if (substr($url, 0, 4) === 'http') {
                    array_push($urls, $url);
                }
            }
        }
        $this->removeUrlDuplicates($urls);

        return $urls;
    }

    /**
     * Group by path and remove duplicates of URLs
     * which only differ in their query strings.
     *
     * @param array $urls
     * @return void
     */
    private function groupUrlsByPath(array &$urls)
    {
        foreach ($urls as $key => $url) {
            $urlBase = current(explode('?', $url, 2));
            // Remove any URLs which differ only by query string
            // by setting URL base as key and assign actual URL as value
            $urls[$urlBase] = $url;
            // Remove old entry by key
            unset($urls[$key]);
        }
        // Reset keys
        $urls = array_values($urls);

        $this->removeUrlDuplicates($urls);
    }

    /**
     * Remove url duplicates.
     *
     * @param array $urls
     * @return void
     */
    private function removeUrlDuplicates(array &$urls)
    {
        $urls = array_values(array_unique($urls, SORT_STRING));
    }

    /**
     * Set timeout configuration of the current database connection.
     *
     * @return void
     * @throws Exception
     */
    private function setDatabaseTimeoutConfiguration()
    {
        // Set timeouts to maximum 1 week
        Db::get()->exec('SET SESSION wait_timeout=604800;');
        Db::get()->exec('SET SESSION interactive_timeout=604800;');
    }

    /**
     * Return timeout configuration of the database.
     *
     * @return array
     * @throws DbException
     */
    private function getDatabaseTimeoutConfiguration()
    {
        return Db::get()->fetchAll('SHOW VARIABLES LIKE "%timeout%"');
    }

    /**
     * Perform audits for every combination of urls,
     * emulated devices and amount of runs.
     *
     * @param int $idSite
     * @param array $urls
     * @param array $emulatedDevices
     * @param array $runs
     * @return void
     * @throws Exception
     */
    private function performAudits(int $idSite, array $urls, array $emulatedDevices, array $runs)
    {
        Piwik::postEvent('Performance.performAudit', [$idSite, $urls, $emulatedDevices, $runs]);
        $this->logDebug('Performing audit for (site ID, URLs, URL count, emulated devices, runs): ' . json_encode([$idSite, $urls, count($urls), $emulatedDevices, $runs], JSON_UNESCAPED_SLASHES));

        foreach ($urls as $url) {
            foreach ($emulatedDevices as $emulatedDevice) {
                foreach ($runs as $run) {
                    try {
                        $this->logInfo('Performing scheduled audit [' . $run . '/'. count($runs) . '] of site ' . $idSite . ' (device: ' . $emulatedDevice . ') for URL: ' . $url);

                        self::getLighthouse($idSite)
                            ->setOutput(sprintf(
                                '%s%s%d-%s-%s-%s%d.json',
                                __DIR__ . DIRECTORY_SEPARATOR,
                                self::AUDIT_FOLDER . DIRECTORY_SEPARATOR,
                                $idSite,
                                $emulatedDevice,
                                sha1($this->getUrlWithoutProtocolAndSubdomain($url, 'www')),
                                ($this->isInDebugMode()) ? 'debug-' : '',
                                $run
                            ))
                            ->setEmulatedDevice($emulatedDevice)
                            ->audit($url);
                        $this->runGarbageCollection();
                    } catch (AuditFailedAuthoriseRefusedException | AuditFailedNotFoundException | AuditFailedMethodNotAllowedException | AuditFailedTooManyRequestsException $exception) {
                        $this->logWarning($exception->getMessage());
                    } catch (AuditFailedException | ProcessTimedOutException | RuntimeException $exception) {
                        $this->logError($exception->getMessage());
                    }
                }
            }
        }

        Piwik::postEvent('Performance.performAudit.end', [$idSite, $urls, $emulatedDevices, $runs]);
    }

    /**
     * Returns iterator for all JSON audit files of specific site.
     *
     * @param int $idSite
     * @return CallbackFilterIterator
     */
    private function getAuditFiles(int $idSite)
    {
        return new CallbackFilterIterator(
            new FilesystemIterator(
                __DIR__ . DIRECTORY_SEPARATOR . self::AUDIT_FOLDER,
                FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
            ),
            function ($file) use ($idSite) {
                $idSiteFile = intval(current(explode('-', $file->getFilename(), 2)));
                $isDebugFile = !!strstr($file->getFilename(), '-debug-');
                // Match if both booleans are logical equal
                $isInMatchingMode = !($this->isInDebugMode() XOR $isDebugFile);

                return $file->getExtension() === 'json' && $idSiteFile === $idSite && $isInMatchingMode;
            }
        );
    }

    /**
     * Do the heavy lifting part here: Parse all audit files,
     * calculate their mean values by groups and return results.
     *
     * @param int $idSite
     * @return array
     */
    private function processAuditFiles(int $idSite)
    {
        $auditFiles = $this->getAuditFiles($idSite);
        $this->logDebug('Process Audit files: ' . json_encode(iterator_to_array($auditFiles)));
        $temporaryResults = [];
        foreach ($auditFiles as $auditFile) {
            $auditFileBasename = $auditFile->getBasename('.' . $auditFile->getExtension());
            [$auditIdSite, $auditEmulatedDevice, $auditUrl, ] = explode('-', $auditFileBasename);

            $audit = json_decode(file_get_contents($auditFile->getPathname()), true);
            $metrics = $audit['audits']['metrics'];
            $score = $audit['categories']['performance']['score'];
            if (!isset($metrics['details']['items'][0])) continue;

            $metricItems = array_intersect_key(
                array_merge($metrics['details']['items'][0], ['score' => intval($score * 100)]),
                array_flip(array_values(Audit::METRICS))
            );

            $currentAudit = &$temporaryResults[$auditIdSite][$auditUrl][$auditEmulatedDevice];
            $this->appendMetricValues($currentAudit, $metricItems);
        }
        $this->logDebug('Audit files processed as: ' . json_encode($temporaryResults));

        if (empty($temporaryResults)) {
            $this->logWarning('Audit files result is empty!');

            return [];
        }

        $results = $this->calculateMetricMinMaxMeanValuesAtDepth($temporaryResults, 3);
        $this->logDebug('Final audit values: ' . json_encode($results));

        return $results;
    }

    /**
     * Append metric values to current audit array object
     * or create one if not present yet.
     *
     * @param ArrayObject|null $currentAudit
     * @param array $metricItems
     * @return void
     */
    private function appendMetricValues(&$currentAudit, array $metricItems)
    {
        if (empty($currentAudit)) {
            $currentAudit = new ArrayObject(array_map(function ($item) {
                return new ArrayObject([$item]);
            }, $metricItems));
        } else {
            foreach ($currentAudit as $metricName => $metricValue) {
                $metricValue->append($metricItems[$metricName]);
            }
        }
    }

    /**
     * Recursively iterate through metric array
     * and calculate min, max and rounded mean values at given depth.
     *
     * @param array $metrics
     * @param int $depth
     * @return array
     */
    private function calculateMetricMinMaxMeanValuesAtDepth(array $metrics, int $depth)
    {
        $recursiveMetricIterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($metrics),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $recursiveMetricIterator->setMaxDepth($depth);

        foreach ($recursiveMetricIterator as $key => $item) {
            if ($recursiveMetricIterator->getDepth() === $depth) {
                $recursiveMetricIterator->getInnerIterator()->offsetSet(
                    $key,
                    [
                        intval(min($item->getArrayCopy())),
                        intval(round($this->calculateMean($item->getArrayCopy()), 0)),
                        intval(max($item->getArrayCopy()))
                    ]
                );
            }
        }

        return $recursiveMetricIterator->getInnerIterator()->getArrayCopy();
    }

    /**
     * Returns mean for array values.
     *
     * @param array $values
     * @return float|int
     */
    private function calculateMean(array $values)
    {
        $count = count($values);
        if ($count < 1) {
            return 0;
        }
        sort($values, SORT_NUMERIC);
        $middleIndex = floor(($count - 1) / 2);
        $middleIndexNext = $middleIndex + 1 - ($count % 2);

        return ($values[$middleIndex] + $values[$middleIndexNext]) / 2;
    }

    /**
     * Removes all audit files for specific site.
     *
     * @param int $idSite
     * @return void
     */
    private function removeAuditFiles(int $idSite)
    {
        $auditFiles = $this->getAuditFiles($idSite);
        foreach ($auditFiles as $auditFile) {
            unlink($auditFile->getPathname());
        }
    }

    /**
     * Returns url without HTTP(S) protocol and given subdomain.
     *
     * @param string $url
     * @param string $subdomain
     * @return string|null
     */
    private function getUrlWithoutProtocolAndSubdomain(string $url, string $subdomain)
    {
        return preg_replace('(^https?://(' . preg_quote($subdomain) . '\.)?)', '', $url);
    }

    /**
     * Stores results in database.
     *
     * @param int $idSite
     * @param array $results
     * @return void
     * @throws Exception
     */
    private function storeResultsInDatabase(int $idSite, array $results)
    {
        if (!isset($results[$idSite])) {
            $this->logWarning('Results for database storage is either empty or site results is not available');

            return;
        }

        $siteResult = $results[$idSite];
        $urls = array_keys($siteResult);
        $actionIdLookupTable = $this->getActionLookupTable($urls, 'id');
        $today = Date::factory('today')->getDatetime();

        if ($this->isInDebugMode()) {
            $this->logInfo('Skipping database storing of results');
            return;
        }

        $rowsInserted = 0;
        foreach ($siteResult as $url => $emulatedDevices) {
            foreach ($emulatedDevices as $emulatedDevice => $metrics) {
                foreach ($metrics as $key => $values) {
                    // Skip URL without an entry in lookup table
                    if (!isset($actionIdLookupTable[$url])) {
                        $this->logWarning('Entry for the following hashed URL in lookup table is missing: ' . $url);
                        continue;
                    }
                    [$min, $median, $max] = $values;

                    $result = Db::get()->query('
                        INSERT INTO `' . Common::prefixTable('log_performance') . '`
                        (`idsite`, `emulated_device`, `idaction`, `key`, `min`, `median`, `max`, `created_at`)
                        VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)
                    ', [
                        $idSite,
                        EmulatedDevice::getIdFor($emulatedDevice),
                        $actionIdLookupTable[$url],
                        $key,
                        $min,
                        $median,
                        $max,
                        $today
                    ]);
                    $rowsInserted += Db::get()->rowCount($result);
                }
            }
        }
        $this->logDebug('Stored ' . $rowsInserted . ' entries in database');
    }

    /**
     * Create lookup table for action information based on
     * their sha1 hash value of its URL.
     *
     * @param array $urls
     * @param string $type
     * @return array
     * @throws DbException
     */
    private function getActionLookupTable(array $urls, string $type)
    {
        if (!in_array($type, ['id', 'url', 'url_prefix'])) {
            throw new OutOfBoundsException($type . ' is invalid value for action lookup table.');
        }

        $whereNamePlaceholder = implode(',', array_fill(0, count($urls), '?'));
        $actionInformation = Db::getReader()->fetchAll('
            SELECT
                `idaction` AS `id`,
                `name` AS `url`,
                `url_prefix`,
                SHA1(`name`) AS `hash`
            FROM ' . Common::prefixTable('log_action') . '
            WHERE
                `type` = ? AND
                SHA1(`name`) IN (' . $whereNamePlaceholder . ')
        ', array_merge(
            [Action::TYPE_PAGE_URL],
            $urls
        ));

        $actionLookupTable = [];
        foreach ($actionInformation as $action) {
            $actionLookupTable[$action['hash']] = $action[$type];
        }
        $this->logDebug('Action IDs lookup table with URLs: ' . json_encode([$actionLookupTable, $urls]));

        return $actionLookupTable;
    }

    /**
     * Forces collection of any existing garbage cycles.
     *
     * @return void
     */
    private function runGarbageCollection()
    {
        if (gc_enabled()) {
            gc_collect_cycles();
        }
    }

    /**
     * Returns log output.
     *
     * @return array
     */
    public function getLogOutput()
    {
        return $this->logOutput;
    }

    /**
     * Sets debug mode to true.
     *
     * @return void
     */
    private function enableDebug()
    {
        $this->debugMode = true;
    }

    /**
     * Returns whether in debug mode or not.
     *
     * @return bool
     */
    private function isInDebugMode()
    {
        return $this->debugMode;
    }

    /**
     * Internal debug logging proxy method.
     *
     * @param string $message
     * @return void
     */
    private function logDebug(string $message)
    {
        if ($this->isInDebugMode()) {
            $this->logOutput[] = '[debug] ' . $message;
        }
        Log::debug($message);
    }

    /**
     * Internal info logging proxy method.
     *
     * @param string $message
     * @return void
     */
    private function logInfo(string $message)
    {
        if ($this->isInDebugMode()) {
            $this->logOutput[] = '[info] ' . $message;
        }
        Log::info($message);
    }

    /**
     * Internal warning logging proxy method.
     *
     * @param string $message
     * @return void
     */
    private function logWarning(string $message)
    {
        if ($this->isInDebugMode()) {
            $this->logOutput[] = '[warning] ' . $message;
        }
        Log::warning($message);
    }

    /**
     * Internal error logging proxy method.
     *
     * @param string $message
     * @return void
     */
    private function logError(string $message)
    {
        if ($this->isInDebugMode()) {
            $this->logOutput[] = '[error] ' . $message;
        }
        Log::error($message);
    }
}
