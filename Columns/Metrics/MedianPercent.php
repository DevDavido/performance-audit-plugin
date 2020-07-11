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

class MedianPercent extends Median
{
    /**
     * Return translated name.
     *
     * @return string
     */
    public function getTranslatedName()
    {
        return Piwik::translate('PerformanceAudit_Metrics_Median_Percent');
    }

    /**
     * Return documentation.
     *
     * @return string
     */
    public function getDocumentation()
    {
        return Piwik::translate('PerformanceAudit_Metrics_Median_Percent_Documentation');
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
        return mb_substr($formatter->getPrettyPercentFromQuotient($value / 100), 0, -1);
    }
}
