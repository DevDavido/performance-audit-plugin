<?php

namespace Piwik\Plugins\PerformanceAudit\Columns\Metrics;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Plugin\Metric;

class Min extends Metric
{
    /**
     * Return name.
     *
     * @return string
     */
    public function getName()
    {
        return 'min';
    }

    /**
     * Return translated name.
     *
     * @return string
     */
    public function getTranslatedName()
    {
        return '';
    }

    /**
     * Return category ID.
     *
     * @return string
     */
    public function getCategoryId()
    {
        return 'PerformanceAudit';
    }
}
