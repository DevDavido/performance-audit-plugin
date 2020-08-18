<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit\Reports;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\Piwik;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;

class GetPerformanceCumulativeLayoutShiftMobile extends GetPerformanceBase
{
    /**
     * Initialise report.
     *
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        $this->name = Piwik::translate('PerformanceAudit_Report_Header_CumulativeLayoutShift_Mobile');
        $this->subcategoryId = Piwik::translate('PerformanceAudit_SubCategory_CumulativeLayoutShift');
        $this->documentation = Piwik::translate('PerformanceAudit_Report_Documentation', [
            Piwik::translate('PerformanceAudit_Report_CumulativeLayoutShift_Documentation_Information'),
            Piwik::translate('PerformanceAudit_EnvironmentMobile'),
            'cls',
            'Cumulative Layout Shift'
        ]);
        $this->order = 7;
    }

    /**
     * Configure widget.
     *
     * @param WidgetsList $widgetsList
     * @param ReportWidgetFactory $factory
     * @return void
     */
    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        parent::configureWidgetsMobile($widgetsList, $factory, 'CumulativeLayoutShift');
    }
}
