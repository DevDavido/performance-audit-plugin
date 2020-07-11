<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit\tests\Unit\Metric;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Warning;
use Piwik\Metrics\Formatter;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MaxPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MedianPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MinPercent;

/**
 * @group Metric
 * @group PercentTest
 * @group PerformanceAudit
 * @group Plugins
 */
class PercentTest extends TestCase
{
    /** @var Formatter */
    private $formatter;

    public function setUp()
    {
        parent::setUp();

        $this->formatter = new Formatter();
    }

    public function test_max_median_min_percent_format_as_expected()
    {
        $objs = [
            new MaxPercent(),
            new MedianPercent(),
            new MinPercent()
        ];

        foreach ($objs as $obj) {
            $this->assertSame('0', $obj->format(0, $this->formatter));
            $this->assertSame('50.125', $obj->format(50.125, $this->formatter));
            $this->assertSame('100', $obj->format(100, $this->formatter));
            $this->assertSame('-100.55', $obj->format(-100.55, $this->formatter));
        }
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function test_max_median_min_percent_format_with_warning()
    {
        $objs = [
            new MaxPercent(),
            new MedianPercent(),
            new MinPercent()
        ];

        foreach ($objs as $obj) {
            $this->assertSame('', $obj->format('test', $this->formatter));
        }
    }
}
