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
use Piwik\Common;
use Piwik\Db;
use Piwik\Log;
use Piwik\Plugin;
use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyOfChromeMissingException;
use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyUnexpectedResultException;
use Piwik\Plugins\PerformanceAudit\Exceptions\DirectoryNotWriteableException;
use Piwik\Plugins\PerformanceAudit\Exceptions\InstallationFailedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\InternetUnavailableException;
use Piwik\SettingsPiwik;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PerformanceAudit extends Plugin
{
    const MINIMUM_NPM_VERSION = 6.13;
    const MINIMUM_CHROME_VERSION = 54.0;

    /**
     * Register plugin events.
     *
     * @return array
     */
    public function registerEvents()
    {
        return [
            'Db.getTablesInstalled' => 'getTablesInstalled',
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
            $this->checkInternetAvailability();
            $this->checkDirectoriesWriteable();
            $this->checkNpm();
            $this->installNpmDependencies();
            $this->checkNpmDependencies();
        } catch (Exception $exception) {
            Log::error('Unable to activate plugin.', ['exception' => $exception]);

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
        $filesToDelete =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'node_modules', RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | RecursiveDirectoryIterator::KEY_AS_PATHNAME | RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

        foreach ($filesToDelete as $file) {
            if ($file->getFilename() === '.gitkeep') {
                continue;
            }
            $action = ($file->isDir() ? 'rmdir' : 'unlink');
            if (!$action($file->getPathname())) {
                return false;
            }
        }

        unlink(__DIR__ . DIRECTORY_SEPARATOR . 'package-lock.json');

        return true;
    }

    /**
     * Check if certain directories are writeable.
     *
     * @return void
     * @throws DirectoryNotWriteableException
     */
    private function checkDirectoriesWriteable()
    {
        $directories = ['Audits', 'node_modules'];
        clearstatcache();
        foreach ($directories as $directory) {
            $directoryPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $directory);
            if (!is_writable($directoryPath)) {
                throw new DirectoryNotWriteableException($directoryPath . ' needs to be a writeable directory.');
            }
        }
    }

    /**
     * Check if NPM (from Node.js) is installed.
     *
     * @return void
     * @throws DependencyUnexpectedResultException
     */
    private function checkNpm()
    {
        $npmVersion = $this->checkDependency('npm', ['-v']);
        if ($npmVersion < self::MINIMUM_NPM_VERSION) {
            throw new DependencyUnexpectedResultException('npm needs to be at least v' . self::MINIMUM_NPM_VERSION . ' but v' . $npmVersion . ' is installed instead.');
        }
    }

    /**
     * Check if dependency is installed.
     *
     * @param string $executableName
     * @param array $args
     * @return float
     * @throws DependencyUnexpectedResultException
     */
    private function checkDependency($executableName, $args)
    {
        $executablePath = ExecutableFinder::search($executableName);
        $process = new Process(array_merge([$executablePath], $args));
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();
            throw (stristr($errorOutput, 'libX11-xcb')) ?
                new DependencyOfChromeMissingException() :
                new DependencyUnexpectedResultException(ucfirst($executableName) . ' has the following unexpected output: ' . PHP_EOL . $errorOutput);
        }

        return floatval(trim(preg_replace('/[^0-9.]/', '', $process->getOutput())));
    }

    /**
     * Install Puppeteer + Lighthouse and its dependencies.
     *
     * @return string
     * @throws DependencyUnexpectedResultException
     */
    private function installNpmDependencies()
    {
        $npmPath = ExecutableFinder::search('npm');
        // Puppeteer + Lighthouse
        $process = new Process([$npmPath, 'install', '--quiet', '--no-progress', '--no-audit', '--force', '--only=production', '--prefix=' . __DIR__, 'puppeteer@^3.0', 'lighthouse@^6.0']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new DependencyUnexpectedResultException('NPM has the following unexpected output: ' . PHP_EOL . $process->getErrorOutput());
        }

        return trim($process->getOutput());
    }

    /**
     * Check if Chrome is properly installed.
     *
     * @return void
     * @throws DependencyUnexpectedResultException
     */
    private function checkNpmDependencies()
    {
        $lighthouse = new Lighthouse();
        $chromeVersion = $this->checkDependency($lighthouse->getChromePath(), ['--version']);
        if ($chromeVersion < self::MINIMUM_CHROME_VERSION) {
            throw new DependencyUnexpectedResultException('Chrome needs to be at least v' . self::MINIMUM_CHROME_VERSION . ' but v' . $chromeVersion . ' is installed instead.');
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

    /**
     * Check if internet connection is required.
     *
     * @return void
     * @throws InternetUnavailableException
     */
    private function checkInternetAvailability()
    {
        if ($this->requiresInternetConnection() && !SettingsPiwik::isInternetEnabled()) {
            throw new InternetUnavailableException('Internet needs to be enabled in order to use this plugin.');
        }
    }
}
