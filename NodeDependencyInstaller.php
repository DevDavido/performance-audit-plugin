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
use Piwik\Log;
use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyNpmMisconfigurationException;
use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyOfChromeMissingException;
use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyUnexpectedResultException;
use Piwik\Plugins\PerformanceAudit\Exceptions\InstallationFailedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\InternetUnavailableException;
use Piwik\SettingsPiwik;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class NodeDependencyInstaller
{
    const MINIMUM_NPM_VERSION = 6.13;
    const MINIMUM_CHROME_VERSION = 54.0;

    /**
     * Run checks and if no error occurs install dependencies.
     *
     * @return bool
     */
    public function install()
    {
        try {
            Helper::checkDirectoriesWriteable(['Audits', 'node_modules']);

            $this->checkInternetAvailability();
            $this->checkNpm();
            $this->installNpmDependencies();
            $this->checkNpmDependencies();
        } catch (Exception $exception) {
            Log::error('Unable to install Node dependencies.', ['exception' => $exception]);

            throw new InstallationFailedException('Node.js dependency installation failed due to the following error: ' . PHP_EOL . $exception->getMessage());
        }

        return true;
    }

    /**
     * Uninstall dependencies.
     *
     * @return bool
     */
    public function uninstall()
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
            $action = ($file->isFile() || $file->isLink()) ? 'unlink' : 'rmdir';
            if (!$action($file->getPathname())) {
                return false;
            }
        }

        unlink(__DIR__ . DIRECTORY_SEPARATOR . 'package-lock.json');

        return true;
    }

    /**
     * Check if NPM (from Node.js) is installed.
     *
     * @return void
     * @throws DependencyUnexpectedResultException
     */
    public function checkNpm()
    {
        $npmVersion = $this->checkDependency('npm', ['-v']);
        if ($npmVersion < self::MINIMUM_NPM_VERSION) {
            throw new DependencyUnexpectedResultException('npm needs to be at least v' . self::MINIMUM_NPM_VERSION . ' but v' . $npmVersion . ' is installed instead.');
        }
    }

    /**
     * Check if Chrome is properly installed.
     *
     * @return void
     * @throws DependencyUnexpectedResultException
     */
    public function checkNpmDependencies()
    {
        $lighthouse = new Lighthouse();
        $chromeVersion = $this->checkDependency($lighthouse->getChromePath(), ['--version']);
        if ($chromeVersion < self::MINIMUM_CHROME_VERSION) {
            throw new DependencyUnexpectedResultException('Chrome needs to be at least v' . self::MINIMUM_CHROME_VERSION . ' but v' . $chromeVersion . ' is installed instead.');
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
            if (stristr($errorOutput, 'libX11-xcb')) {
                throw new DependencyOfChromeMissingException();
            } elseif (stristr($errorOutput, 'cache folder contains root-owned files')) {
                throw new DependencyNpmMisconfigurationException($executableName . ' has the following issue: ' . PHP_EOL . $errorOutput);
            }

            throw new DependencyUnexpectedResultException(ucfirst($executableName) . ' has the following unexpected output: ' . PHP_EOL . $errorOutput);
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
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new DependencyUnexpectedResultException('NPM has the following unexpected output: ' . PHP_EOL . $process->getErrorOutput());
        }

        return trim($process->getOutput());
    }

    /**
     * Check if internet connection is required.
     *
     * @return void
     * @throws InternetUnavailableException
     */
    public function checkInternetAvailability()
    {
        if (!SettingsPiwik::isInternetEnabled()) {
            throw new InternetUnavailableException('Internet needs to be enabled in order to install plugin dependencies.');
        }
    }
}
