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
use Piwik\DataTable\Filter\Sort;
use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MaxPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MedianPercent;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MinPercent;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;

class GetPerformanceScoreDesktop extends GetPerformanceBase
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

        $this->name = Piwik::translate('PerformanceAudit_Report_Header_Score_Desktop');
        $this->subcategoryId = Piwik::translate('PerformanceAudit_SubCategory_Score');
        $this->documentation = Piwik::translate('PerformanceAudit_Report_Documentation', [
            Piwik::translate('PerformanceAudit_Report_Score_Documentation_Information'),
            Piwik::translate('PerformanceAudit_EnvironmentDesktop'),
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
        parent::configureWidgetsDesktop($widgetsList, $factory, 'Score');
    }
}
