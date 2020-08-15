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
use Piwik\Plugin;
use Piwik\Plugin\Controller as BaseController;
use Piwik\Site;

class Controller extends BaseController
{
    /**
     * Get plugin information and render it.
     *
     * @return string
     */
    public function version()
    {
        $plugin = new Plugin('PerformanceAudit');

        return $this->renderTemplate('version', [
            'pluginVersion' => $plugin->getVersion(),
            'pluginName' => $plugin->getPluginName()
        ]);
    }

    /**
     * Render plugin check page.
     *
     * @return string
     * @throws Exception
     */
    public function pluginCheck()
    {
        $installer = new NodeDependencyInstaller();

        $error = '';
        try {
            Helper::checkDirectoriesWriteable(['Audits', 'node_modules']);

            $installer->checkInternetAvailability();
            $installer->checkNpm();
            $installer->checkNpmDependencies();
        } catch (Exception $exception) {
            $error = $exception->getMessage();
        }

        return $this->renderTemplate('pluginCheck', [
            'checkStartUrl' => (new Menu())->getUrlForAction('pluginCheckStart'),
            'error' => $error
        ]);
    }

    /**
     * Start plugin check.
     *
     * @return string
     * @throws Exception
     */
    public function pluginCheckStart()
    {
        $tasks = new Tasks();
        foreach (Site::getSites() as $site) {
            $tasks->auditSite((int) $site['idsite'], $debug = true);
        }

        $hasErrorInOutput = false;
        $logOutput = array_map('trim', array_map('stripslashes', $tasks->getLogOutput()));
        foreach ($logOutput as $logEntry) {
            if (stristr($logEntry, '[error]') || stristr($logEntry, '[warning]')) {
                $hasErrorInOutput = true;
            }
        }

        return $this->renderTemplate('pluginChecked', [
            'hasErrorInOutput' => $hasErrorInOutput,
            'logOutput' => nl2br(implode("\n", $logOutput))
        ]);
    }
}
