<?php

namespace Piwik\Plugins\PerformanceAudit\Reports;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\Piwik;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;

class GetPerformanceCumulativeLayoutShiftDesktop extends GetPerformanceBase
{
    /**
     * Initialise report.
     *
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        $this->name = Piwik::translate('PerformanceAudit_Report_Header_CumulativeLayoutShift_Desktop');
        $this->subcategoryId = Piwik::translate('PerformanceAudit_SubCategory_CumulativeLayoutShift');
        $this->documentation = Piwik::translate('PerformanceAudit_Report_Documentation', [
            Piwik::translate('PerformanceAudit_Report_CumulativeLayoutShift_Documentation_Information'),
            Piwik::translate('PerformanceAudit_EnvironmentDesktop'),
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
        parent::configureWidgetsDesktop($widgetsList, $factory, 'CumulativeLayoutShift');
    }
}
