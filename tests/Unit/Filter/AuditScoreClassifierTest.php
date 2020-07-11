<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit\tests\Unit\Filter;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\PerformanceAudit\Columns\Filters\AuditScoreClassifier;

/**
 * @group Filter
 * @group AuditScoreClassifier
 * @group PerformanceAudit
 * @group Plugins
 */
class AuditScoreClassifierTest extends TestCase
{
    public function test_get_correct_score_tooltips()
    {
        $tooltips = [
            AuditScoreClassifier::getTooltip('100%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('90%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('89.999%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('50%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('49.999%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('0%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('-1%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('200%', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('', 'PerformanceAudit_Report_Score_Mobile'),
            AuditScoreClassifier::getTooltip('test', 'PerformanceAudit_Report_Score_Mobile'),
        ];

        $this->assertSame([
            "100% is classified as FAST! <br />\n Values between 90% – 100% are in this classification group.",
            "90% is classified as FAST! <br />\n Values between 90% – 100% are in this classification group.",
            "89.999% is classified as MODERATE! <br />\n Values between 50% – 90% are in this classification group.",
            "50% is classified as MODERATE! <br />\n Values between 50% – 90% are in this classification group.",
            "49.999% is classified as SLOW! <br />\n Values between 0% – 50% are in this classification group.",
            "0% is classified as SLOW! <br />\n Values between 0% – 50% are in this classification group.",
            "-1% is out of range of all classification groups.",
            "200% is out of range of all classification groups.",
            "",
            ""
        ], $tooltips);
    }

    public function test_get_correct_first_contentful_paint_tooltips()
    {
        $tooltips = [
            AuditScoreClassifier::getTooltip('0', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('1.999', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('2', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('3.999', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('4', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('5', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('60', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('200', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
            AuditScoreClassifier::getTooltip('test', 'PerformanceAudit_Report_FirstContentfulPaint_Mobile'),
        ];

        $this->assertSame([
            "0s is classified as FAST! <br />\n Values between 0s – 2s are in this classification group.",
            "1.999s is classified as FAST! <br />\n Values between 0s – 2s are in this classification group.",
            "2s is classified as MODERATE! <br />\n Values between 2s – 4s are in this classification group.",
            "3.999s is classified as MODERATE! <br />\n Values between 2s – 4s are in this classification group.",
            "4s is classified as SLOW! <br />\n Values between 4s – 60s are in this classification group.",
            "5s is classified as SLOW! <br />\n Values between 4s – 60s are in this classification group.",
            "60s is classified as SLOW! <br />\n Values between 4s – 60s are in this classification group.",
            "200s is out of range of all classification groups.",
            "",
            ""
        ], $tooltips);
    }
}
