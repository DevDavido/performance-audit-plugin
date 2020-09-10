<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Common;
use Piwik\Updater;
use Piwik\Updater\Migration\Db;
use Piwik\Updater\Migration\Factory as MigrationFactory;
use Piwik\Updates as PiwikUpdates;

/**
 * Update for version 1.1.0.
 */
class Updates_1_1_0 extends PiwikUpdates
{
    /**
     * @var MigrationFactory
     */
    private $migration;

    /**
     * Constructor for update.
     *
     * @param MigrationFactory $factory
     */
    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    /**
     * Return database migrations to be executed in this update.
     *
     * @param Updater $updater
     * @return Db[]
     */
    public function getMigrations(Updater $updater)
    {
        $migration = $this->migration->db->boundSql('
            UPDATE `' . Common::prefixTable('site_setting') . '`
            SET
                `setting_name` = ?
            WHERE
                `plugin_name` = ? AND
                `setting_name` = ?
        ', [
            'has_grouped_urls',
            'PerformanceAudit',
            'has_urls_without_query_string'
        ]);

        return [$migration];
    }

    /**
     * Perform the incremental version update.
     *
     * @param Updater $updater
     */
    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
