<?php

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Log\Logger;
use Piwik\Plugin;
use Piwik\Plugins\PerformanceAudit\Exceptions\InstallationFailedException;
use ReflectionClass;
use ReflectionException;

class PerformanceAudit extends Plugin
{
    /**
     * Register plugin events.
     *
     * @return array
     */
    public function registerEvents()
    {
        return [
            'Db.getTablesInstalled' => 'getTablesInstalled',
            'Updater.componentUpdated' => 'updated',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles'
        ];
    }

    /**
     * Create database table(s).
     *
     * @return void
     * @throws Exception
     */
    public function install()
    {
        $this->createDatabaseTable();
    }

    /**
     * Delete database table(s).
     *
     * @return void
     */
    public function uninstall()
    {
        $this->deleteDatabaseTable();
    }

    /**
     * Activate plugin.
     *
     * @return bool
     */
    public function activate()
    {
        try {
            (new NodeDependencyInstaller())->install();
        } catch (Exception $exception) {
            StaticContainer::get(Logger::class)->error('Unable to activate plugin.', ['exception' => $exception]);

            // Throw new exception so the plugin doesn't get activated in Matomo
            throw new InstallationFailedException('PerformanceAudit plugin activation failed due to the following error: ' . PHP_EOL . $exception->getMessage());
        }

        return true;
    }

    /**
     * Deactivate plugin.
     *
     * @return bool
     */
    public function deactivate()
    {
        return (new NodeDependencyInstaller())->uninstall();
    }

    /**
     * Called event after component update.
     *
     * @param string $componentName
     * @param string $updatedVersion
     * @return void
     * @throws ReflectionException
     */
    public function updated($componentName, $updatedVersion) {
        // Only perform action if this plugin got updated
        if ((new ReflectionClass($this))->getShortName() === $componentName) {
            StaticContainer::get(Logger::class)->info($componentName . ' plugin was updated to version: ' . $updatedVersion);

            // Since an plugin update removes all installed Node dependencies,
            // we re-add them by running the dependencies installer via activate()
            // Nice side effect: Dependencies get directly updated
            $this->activate();
        }
    }

    /**
     * Create log database table.
     *
     * @return void
     * @throws Exception
     */
    private function createDatabaseTable()
    {
        Db::exec('
            CREATE TABLE IF NOT EXISTS `' . Common::prefixTable('log_performance') . '` (
                `idreport` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `idsite` INT UNSIGNED NOT NULL,
                `emulated_device` TINYINT UNSIGNED NOT NULL,
                `idaction` INT UNSIGNED NOT NULL,
                `key` VARCHAR(255) COLLATE utf8_general_ci NOT NULL,
                `min` INT UNSIGNED NOT NULL,
                `median` INT UNSIGNED NOT NULL,
                `max` INT UNSIGNED NOT NULL,
                `created_at` DATE NOT NULL,

                PRIMARY KEY (`idreport`),
                INDEX (`idsite`),
                INDEX (`idaction`),
                INDEX (`emulated_device`),
                INDEX (`key`),
                INDEX (`created_at`)
            )  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci
        ');
    }

    /**
     * Delete log database table.
     *
     * @return void
     */
    private function deleteDatabaseTable()
    {
        Db::dropTables(Common::prefixTable('log_performance'));
    }

    /**
     * Register the new tables.
     *
     * @param array $tablesInstalled
     * @retrun void
     */
    public function getTablesInstalled(&$tablesInstalled)
    {
        $tablesInstalled[] = Common::prefixTable('log_performance');
    }

    /**
     * Sets array of required CSS files.
     *
     * @param array $cssFiles
     * @retrun void
     */
    public function getStylesheetFiles(&$cssFiles)
    {
        $cssFiles[] = 'plugins/PerformanceAudit/stylesheets/pluginCheck.css';
    }

    /**
     * Return if internet connection is required.
     *
     * @return bool
     */
    public function requiresInternetConnection() {
        return true;
    }
}
