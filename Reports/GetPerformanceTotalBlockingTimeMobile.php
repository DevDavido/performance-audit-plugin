<?php

namespace Piwik\Plugins\PerformanceAudit\Reports;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\Piwik;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;

class GetPerformanceTotalBlockingTimeMobile extends GetPerformanceBase
{
    /**
     * Initialise report.
     *
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        $this->name = Piwik::translate('PerformanceAudit_Report_Header_TotalBlockingTime_Mobile');
        $this->subcategoryId = Piwik::translate('PerformanceAudit_SubCategory_TotalBlockingTime');
        $this->documentation = Piwik::translate('PerformanceAudit_Report_Documentation', [
            Piwik::translate('PerformanceAudit_Report_TotalBlockingTime_Documentation_Information'),
            Piwik::translate('PerformanceAudit_EnvironmentMobile'),
            'lighthouse-total-blocking-time',
            'Total Blocking Time'
        ]);
        $this->order = 6;
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
        parent::configureWidgetsMobile($widgetsList, $factory, 'TotalBlockingTime');
    }
}
