<?php

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Menu\MenuAdmin;
use Piwik\Plugin\Menu as BaseMenu;

/**
 * API-Reference https://developer.matomo.org/api-reference/Piwik/Menu/MenuAbstract
 */
class Menu extends BaseMenu
{
    /**
     * Configure admin menu.
     *
     * @param MenuAdmin $menu
     * @return void
     */
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $menu->addItem('PerformanceAudit_CoreAdminHome_MenuPerformance', 'PerformanceAudit_PluginCheck', $this->urlForAction('pluginCheck'), $orderId = 4);
        $menu->addItem('PerformanceAudit_CoreAdminHome_MenuPerformance', 'PerformanceAudit_Version', $this->urlForAction('version'), $orderId = 5);
    }

    /**
     * Returns URL for action.
     *
     * @param string $name
     * @return string
     */
    public function getUrlForAction(string $name)
    {
        return http_build_query($this->urlForActionWithDefaultUserParams($name));
    }
}
