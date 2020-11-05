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
use Piwik\Common;
use Piwik\DataTable\Filter\Sort;
use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MaxSeconds;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MedianSeconds;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\MinSeconds;
use Piwik\Plugins\PerformanceAudit\Columns\PageUrl;
use Piwik\Plugins\PerformanceAudit\EmulatedDevice;
use Piwik\Plugins\PerformanceAudit\MeasurableSettings;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;
use ReflectionClass;

class GetPerformanceBase extends Report
{
    /**
     * Emulated device.
     *
     * @var string
     */
    protected $emulatedDevice;

    /**
     * Initial general metrics and settings.
     *
     * @throws Exception
     */
    protected function init()
    {
        if (!Common::getRequestVar('idSite', false)) {
            return;
        }

        $siteSettings = new MeasurableSettings(Common::getRequestVar('idSite'));
        $defaultMetrics = [
            new MinSeconds(),
            new MedianSeconds(),
            new MaxSeconds()
        ];

        $this->name = 'PerformanceAudit_Base';
        $this->dimension = new PageUrl();
        $this->categoryId = Piwik::translate('PerformanceAudit_Category');

        $this->metrics = $defaultMetrics;
        $this->defaultSortColumn = (new MedianSeconds())->getName();
        $this->processedMetrics = $defaultMetrics;

        $this->supportsFlatten = false;
        $this->constantRowsCount = true;

        $this->emulatedDevice = $siteSettings->getSetting('emulated_device')->getValue();
    }

    /**
     * Check if user has access or not.
     *
     * @return bool
     * @throws Exception
     */
    public function isEnabled()
    {
        $idSite = Common::getRequestVar('idSite', false);
        if (!$idSite) {
            return false;
        }
        // Disable report if initialised as instance of itself
        if ((new ReflectionClass($this))->getShortName() === 'GetPerformanceBase') {
            return false;
        }

        $siteSettings = new MeasurableSettings($idSite);
        if (!$siteSettings->isAuditEnabled()) {
            return false;
        }

        return Piwik::isUserHasViewAccess($idSite);
    }

    /**
     * Configure view.
     *
     * @param ViewDataTable $view
     * @return void
     */
    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = true;
        $view->config->search_recursive = false;

        $view->config->show_exclude_low_population = false;
        $view->config->show_flatten_table = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_pie_chart = false;
        $view->config->show_tag_cloud = false;
        $view->config->show_bar_chart = false;
        $view->config->show_insights = false;

        $view->config->addTranslation('label', $this->dimension->getName());

        $this->recursiveLabelSeparator = '/';
        $view->config->datatable_js_type = 'ActionsDataTable';

        $view->requestConfig->filter_sort_column = (new MedianSeconds())->getName();
        $view->requestConfig->filter_sort_order = Sort::ORDER_DESC;

        $view->config->columns_to_display = [
            'label',
            (new MinSeconds())->getName(),
            (new MedianSeconds())->getName(),
            (new MaxSeconds())->getName()
        ];
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
        // Needs to stay empty so no widget will get created
    }

    /**
     * Configure mobile widget.
     *
     * @param WidgetsList $widgetsList
     * @param ReportWidgetFactory $factory
     * @param string $widgetName
     * @return void
     */
    public function configureWidgetsMobile(WidgetsList $widgetsList, ReportWidgetFactory $factory, string $widgetName)
    {
        $isEnabled = $this->emulatedDevice !== EmulatedDevice::Desktop;
        $hasWideWidget = $this->emulatedDevice === EmulatedDevice::Mobile;

        if (!$isEnabled) {
            return;
        }

        $widget = $factory->createWidget()
            ->setName('PerformanceAudit_Report_Header_' . $widgetName . '_' . ucfirst(EmulatedDevice::Mobile))
            ->setOrder(1);

        if ($hasWideWidget) {
            $widget->setIsWide();
        }

        $widgetsList->addWidgetConfig($widget);
    }

    /**
     * Configure desktop widget.
     *
     * @param WidgetsList $widgetsList
     * @param ReportWidgetFactory $factory
     * @param string $widgetName
     * @return void
     */
    public function configureWidgetsDesktop(WidgetsList $widgetsList, ReportWidgetFactory $factory, string $widgetName)
    {
        $isEnabled = $this->emulatedDevice !== EmulatedDevice::Mobile;
        $hasWideWidget = $this->emulatedDevice === EmulatedDevice::Desktop;

        if (!$isEnabled) {
            return;
        }

        $widget = $factory->createWidget()
            ->setName('PerformanceAudit_Report_Header_' . $widgetName . '_' . ucfirst(EmulatedDevice::Desktop))
            ->setOrder(2);

        if ($hasWideWidget) {
            $widget->setIsWide()->setOrder(1);
        }

        $widgetsList->addWidgetConfig($widget);
    }
}
