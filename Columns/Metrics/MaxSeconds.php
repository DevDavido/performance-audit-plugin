<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit\Columns\Metrics;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Metrics\Formatter;
use Piwik\Piwik;

class MaxSeconds extends Max
{
    /**
     * Return translated name.
     *
     * @return string
     */
    public function getTranslatedName()
    {
        return Piwik::translate('PerformanceAudit_Metrics_Max_Seconds');
    }

    /**
     * Return documentation.
     *
     * @return string
     */
    public function getDocumentation()
    {
        return Piwik::translate('PerformanceAudit_Metrics_Max_Seconds_Documentation');
    }

    /**
     * Returns a formatted value.
     *
     * @param mixed $value
     * @param Formatter $formatter
     * @return mixed $value
     */
    public function format($value, Formatter $formatter)
    {
        if ($value == 0) {
            return '0.000';
        }

        return $formatter->getPrettyNumber($value / 1000, 3);
    }
}
