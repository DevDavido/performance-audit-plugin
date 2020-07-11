<?php
/**
* Matomo - free/libre analytics platform
*
* @link https://matomo.org
* @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
*/

namespace Piwik\Plugins\PerformanceAudit\Columns\Filters;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Piwik;

class AuditScoreClassifier
{
    /**
     * Holds all classifying information for audit categories.
     *
     * @var array
     */
    private const RANGES = [
        'score' => [
            'slow' => [0, 50],
            'moderate' => [50, 90],
            'fast' => [90, 100]
        ],
        'firstContentfulPaint' => [
            'fast' => [0, 2],
            'moderate' => [2, 4],
            'slow' => [4, 60]
        ],
        'speedIndex' => [
            'fast' => [0, 4.4],
            'moderate' => [4.4, 5.8],
            'slow' => [5.8, 60]
        ],
        'largestContentfulPaint' => [
            'fast' => [0, 2.5],
            'moderate' => [2.5, 4.0],
            'slow' => [4.0, 60]
        ],
        'interactive' => [
            'fast' => [0, 3.8],
            'moderate' => [3.8, 7.3],
            'slow' => [7.3, 60]
        ],
        'totalBlockingTime' => [
            'fast' => [0, 0.3],
            'moderate' => [0.3, 0.6],
            'slow' => [0.6, 10]
        ],
        'cumulativeLayoutShift' => [
            'fast' => [0, 0.1],
            'moderate' => [0.1, 0.25],
            'slow' => [0.25, 10]
        ]
    ];

    /**
     * Return tooltip string.
     *
     * @param string $metric
     * @param string $dataTableName
     * @return string
     */
    public static function getTooltip(string $metric, string $dataTableName)
    {
        $isUnitPercent = mb_strstr($dataTableName, '_Score_');
        $unitSuffix = ($isUnitPercent) ? '%' : 's';
        $value = str_replace($unitSuffix, '', $metric);

        if (!is_numeric($value)) {
            return '';
        }

        preg_match('/((.*)_){2}(.*)_/', $dataTableName, $matches);
        $metricCategory = lcfirst($matches[3]);
        $ranges = self::RANGES[$metricCategory];

        foreach ($ranges as $classification => $boundaries) {
            $lowerBoundary = $boundaries[0];
            $upperBoundary = $boundaries[1];
            $isLastBoundary = $boundaries === end($ranges);
            if (self::isInRange($value, $lowerBoundary, $upperBoundary, $isLastBoundary)) {
                return nl2br(Piwik::translate('PerformanceAudit_Metrics_Tooltip', [
                    $value,
                    $unitSuffix,
                    mb_strtoupper($classification),
                    $lowerBoundary,
                    $upperBoundary,
                ]), true);
            }
        }

        return Piwik::translate('PerformanceAudit_Metrics_Tooltip_OutOfRange', [$value, $unitSuffix]);
    }

    /**
     * Checks if number is within range.
     *
     * @param float|int $value
     * @param float $lowerBoundary
     * @param float $upperBoundary
     * @param bool $includingEqualUpperBound
     * @return bool
     */
    private static function isInRange($value, float $lowerBoundary, float $upperBoundary, bool $includingEqualUpperBound = true)
    {
        return ($lowerBoundary <= $value) && (($includingEqualUpperBound) ? ($value <= $upperBoundary) : ($value < $upperBoundary));
    }
}
