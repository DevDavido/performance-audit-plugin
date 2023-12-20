<?php

namespace Piwik\Plugins\PerformanceAudit\Columns\Metrics;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Plugin\Metric;

class Max extends Metric
{
    /**
     * Return name.
     *
     * @return string
     */
    public function getName()
    {
        return 'max';
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
