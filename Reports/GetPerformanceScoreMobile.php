<?php

namespace Piwik\Plugins\PerformanceAudit\Reports;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\DataTable\Filter\Sort;
use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MaxPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MedianPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MinPercent;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;

class GetPerformanceScoreMobile extends GetPerformanceBase
{
    /**
     * Initialise report.
     *
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        $metrics = [
            new MinPercent(),
            new MedianPercent(),
            new MaxPercent()
        ];
        $this->metrics = $metrics;
        $this->processedMetrics = $metrics;
        $this->defaultSortColumn = (new MedianPercent())->getName();
        $this->defaultSortOrderDesc = false;

        $this->name = Piwik::translate('PerformanceAudit_Report_Header_Score_Mobile');
        $this->subcategoryId = Piwik::translate('PerformanceAudit_SubCategory_Score');
        $this->documentation = Piwik::translate('PerformanceAudit_Report_Documentation', [
            Piwik::translate('PerformanceAudit_Report_Score_Documentation_Information'),
            Piwik::translate('PerformanceAudit_EnvironmentMobile'),
            'performance-scoring',
            'Lighthouse Performance Score'
        ]);
        $this->order = 1;
    }

    /**
     * Configure view.
     *
     * @param ViewDataTable $view
     * @return void
     */
    public function configureView(ViewDataTable $view)
    {
        parent::configureView($view);

        $view->requestConfig->filter_sort_column = (new MedianPercent())->getName();
        $view->requestConfig->filter_sort_order = Sort::ORDER_ASC;
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
        parent::configureWidgetsMobile($widgetsList, $factory, 'Score');
    }
}
