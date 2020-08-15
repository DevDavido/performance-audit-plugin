<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Updater;

/**
 * Update for version 1.0.7.
 */
class Updates_1_0_7 extends BaseUpdate
{
    /**
     * Perform the incremental version update.
     *
     * @param Updater $updater
     */
    public function doUpdate(Updater $updater)
    {
        parent::doBaseUpdate($updater);
    }
}
