<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit\Columns\Metrics;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Plugins\PerformanceAudit\Lighthouse;

class Audit
{
    /**
     * All relevant audit information.
     *
     * @var array
     */
    public const METRICS = [
        'first-contentful-paint' => 'firstContentfulPaint',
        'speed-index' => 'speedIndex',
        'largest-contentful-paint' => 'largestContentfulPaint',
        'interactive' => 'interactive',
        'total-blocking-time' => 'totalBlockingTime',
        'cumulative-layout-shift' => 'cumulativeLayoutShift',
        'score' => 'score'
    ];

    /**
     * Enables all metrics for given lighthouse instance.
     *
     * @param Lighthouse $lighthouse
     * @return void
     */
    public static function enableEachLighthouseAudit(Lighthouse $lighthouse) {
        $audits = array_keys(self::METRICS);
        foreach ($audits as $audit) {
            $lighthouse->enableAudit($audit);
        }
    }
}
