<?php

namespace Piwik\Plugins\PerformanceAudit\Columns\Metrics;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use TypeError;

class MaxPercent extends Max
{
    /**
     * Return translated name.
     *
     * @return string
     */
    public function getTranslatedName()
    {
        return Piwik::translate('PerformanceAudit_Metrics_Max_Percent');
    }

    /**
     * Return documentation.
     *
     * @return string
     */
    public function getDocumentation()
    {
        return Piwik::translate('PerformanceAudit_Metrics_Max_Percent_Documentation');
    }

    /**
     * Returns a formatted value.
     *
     * @param mixed $value
     * @param Formatter $formatter
     * @return mixed $value
     * @throws TypeError
     */
    public function format($value, Formatter $formatter)
    {
        if (!is_numeric($value)) {
            throw new TypeError("A non-numeric value encountered");
        }

        return mb_substr($formatter->getPrettyPercentFromQuotient($value / 100), 0, -1);
    }
}
