<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Dzava\Lighthouse\Lighthouse as BaseLighthouse;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedAuthoriseRefusedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedMethodNotAllowedException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedNotFoundException;
use Piwik\Plugins\PerformanceAudit\Exceptions\AuditFailedTooManyRequestsException;
use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyMissingException;
use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyUnexpectedResultException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use UnexpectedValueException;

class Lighthouse extends BaseLighthouse
{
    protected $audits = [];

    /**
     * Setup and find lighthouse executable.
     *
     * @throws DependencyMissingException
     */
    public function __construct()
    {
        parent::__construct();

        $this->setChromePath($this->getChromePath());
        $this->setLighthousePath(ExecutableFinder::search('lighthouse'));
        $this->setTimeout(60);
    }

    /**
     * Perform audit of URL.
     *
     * @param string $url
     * @return string
     * @throws AuditFailedAuthoriseRefusedException|AuditFailedNotFoundException
     * @throws AuditFailedMethodNotAllowedException|AuditFailedException
     * @throws RuntimeException|ProcessTimedOutException|ProcessSignaledException
     */
    public function audit($url)
    {
        $command = $this->getCommand($url);
        $env = ['CHROME_PATH' => $this->getChromePath()];
        $process = new Process($command, $env);

        $process->setTimeout($this->timeout)->run();

        if (!$process->isSuccessful()) {
            $this->throwAuditException($url, $process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * Create command by building config if needed and merging options.
     *
     * @param string $url
     * @return array
     */
    public function getCommand($url)
    {
        if ($this->configPath === null || $this->config !== null) {
            $this->buildConfig();
        }

        $command = array_merge([
            $this->lighthousePath,
            $this->outputFormat,
            $this->headers,
            '--quiet',
            '--config-path=' . $this->configPath,
            $url,
        ], $this->processOptions());

        return array_filter($command);
    }

    /**
     * Returns Chrome path from bundled browser of puppeteer.
     *
     * @return string
     * @throws DependencyUnexpectedResultException
     */
    public function getChromePath()
    {
        if (!is_null($this->chromePath)) {
            return $this->chromePath;
        }

        $jsFilePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'ChromePath.js');
        $npmPath = ExecutableFinder::search('node');
        $process = new Process([$npmPath, '--no-warnings', $jsFilePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new DependencyUnexpectedResultException('NPM has the following unexpected output: ' . PHP_EOL . $process->getErrorOutput());
        }

        return trim($process->getOutput());
    }

    /**
     * Set emulated device.
     *
     * @param string $device
     * @return self
     */
    public function setEmulatedDevice(string $device)
    {
        if (!in_array($device, ['desktop', 'mobile'])) {
            throw new UnexpectedValueException('Lighthouse emulated device has no valid value.');
        }
        $this->setOption('--emulated-form-factor', $device);

        return $this;
    }

    /**
     * Set Chrome path.
     *
     * @param string $path
     * @return self
     */
    public function setChromePath($path)
    {
        $this->chromePath = $path;

        return $this;
    }

    /**
     * Disable all device emulation.
     *
     * @return self
     */
    public function disableDeviceEmulation()
    {
        $this->setOption('--emulated-form-factor', 'none');
        $this->setOption('--throttling-method', 'provided');

        return $this;
    }

    /**
     * Enable audit.
     *
     * @param string $audit
     * @return self
     */
    public function enableAudit($audit)
    {
        $this->setAudit($audit, true);

        return $this;
    }

    /**
     * Creates the config file used during the audit.
     *
     * @return self
     */
    protected function buildConfig()
    {
        $config = tmpfile();
        $this->withConfig(stream_get_meta_data($config)['uri']);
        $this->config = $config;

        $r = 'module.exports = ' . json_encode([
                'extends' => 'lighthouse:default',
                'settings' => [
                    'onlyCategories' => $this->categories,
                    'onlyAudits' => $this->audits,
                ],
            ]);
        fwrite($config, $r);

        return $this;
    }

    /**
     * Enable or disable a audit.
     *
     * @param string $audit
     * @param bool $enable
     * @return self
     */
    protected function setAudit($audit, $enable)
    {
        $index = array_search($audit, $this->audits);

        if ($index !== false) {
            if ($enable == false) {
                unset($this->audits[$index]);
            }
        } elseif ($enable) {
            $this->audits[] = $audit;
        }

        return $this;
    }

    /**
     * Throw an corresponding exception if audit fails.
     *
     * @param string $url
     * @param string $errorOutput
     * @return void
     * @throws AuditFailedAuthoriseRefusedException|AuditFailedNotFoundException
     * @throws AuditFailedMethodNotAllowedException|AuditFailedException
     * @throws AuditFailedTooManyRequestsException
     */
    protected function throwAuditException($url, $errorOutput)
    {
        if (stristr($errorOutput, 'Status code: 403')) {
            throw new AuditFailedAuthoriseRefusedException($url, $errorOutput);
        } elseif (stristr($errorOutput, 'Status code: 404')) {
            throw new AuditFailedNotFoundException($url, $errorOutput);
        } elseif (stristr($errorOutput, 'Status code: 405')) {
            throw new AuditFailedMethodNotAllowedException($url, $errorOutput);
        } elseif (stristr($errorOutput, 'Status code: 429')) {
            throw new AuditFailedTooManyRequestsException($url, $errorOutput);
        }

        throw new AuditFailedException($url, $errorOutput);
    }
}
