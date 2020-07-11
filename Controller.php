<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Plugin;
use Piwik\Plugin\Controller as BaseController;

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
}
