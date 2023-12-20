<?php

namespace Piwik\Plugins\PerformanceAudit\tests\Integration;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use DI\NotFoundException;
use Exception;
use Piwik\Access;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Db;
use Piwik\Plugins\PerformanceAudit\Columns\Metrics\Audit;
use Piwik\Plugins\PerformanceAudit\EmulatedDevice;
use Piwik\Plugins\PerformanceAudit\MeasurableSettings;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Plugins\UsersManager\UserUpdater;
use Piwik\Site;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

abstract class PerformanceAuditIntegrationPreparation extends IntegrationTestCase
{
    const SERVER_HOST_NAME = 'localhost';
    const SERVER_PORT = 80;

    const TEST_SUPERUSER_LOGIN = 'batman';
    const TEST_SUPERUSER_PASS = 'loveseveryone';

    private $superUserTokenAuth;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        if (!$this->isServerRunning()) {
            throw new Exception('Server not found on port localhost:80. For integration tests, an server must be running.');
        }

        parent::setUp();

        // Create user
        $this->addPreexistingSuperUser();
        $this->superUserTokenAuth = UsersManagerAPI::getInstance()->createAppSpecificTokenAuth(
            self::TEST_SUPERUSER_LOGIN,
            self::TEST_SUPERUSER_PASS,
            "app-specific-pwd-description"
        );

        // Create sites
        [$website1Id, $website2Id, $website3Id] = [
            Fixture::createWebsite('2013-01-01 00:00:00', 0, 'Example 1', 'https://example.com/'),
            Fixture::createWebsite('2013-01-01 00:00:00', 0, 'Example 2', 'https://example.org/'),
            Fixture::createWebsite('2013-01-01 00:00:00', 0, 'Example 3', 'https://example.net/')
        ];
        $this->createBaseData([$website1Id, $website2Id, $website3Id]);
    }

    /**
     * @return bool
     */
    private function isServerRunning()
    {
        $fp = @fsockopen(self::SERVER_HOST_NAME, self::SERVER_PORT, $errno, $errstr, 5);
        if (empty($fp)) {
            return false;
        } else {
            fclose($fp);
            return true;
        }
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    protected function addPreexistingSuperUser()
    {
        UsersManagerAPI::getInstance()->addUser(self::TEST_SUPERUSER_LOGIN, self::TEST_SUPERUSER_PASS, 'test@gmail.com');
        $this->setSuperUserAccess(self::TEST_SUPERUSER_LOGIN, true);

        $auth = StaticContainer::get('Piwik\Auth');
        $auth->setLogin(self::TEST_SUPERUSER_LOGIN);
        $auth->setPassword(self::TEST_SUPERUSER_PASS);
        Access::getInstance()->setSuperUserAccess(false);
        Access::getInstance()->reloadAccess(StaticContainer::get('Piwik\Auth'));
    }

    /**
     * @param string $user
     * @param bool $hasAccess
     * @throws Exception
     */
    protected function setSuperUserAccess($user, $hasAccess)
    {
        $userUpdater = new UserUpdater();
        if (method_exists($userUpdater, 'setSuperUserAccessWithoutCurrentPassword')) {
            $userUpdater->setSuperUserAccessWithoutCurrentPassword($user, $hasAccess);
        } else {
            UsersManagerAPI::getInstance()->setSuperUserAccess($user, $hasAccess);
        }
    }

    /**
     * @param array $siteIds
     * @throws Exception
     */
    private function createBaseData(array $siteIds)
    {
        [$website1Id, $website2Id, $website3Id] = $siteIds;

        $settingsEmulatedDevices = [
            $website1Id => [EmulatedDevice::Mobile, EmulatedDevice::Desktop],
            $website2Id => [EmulatedDevice::Desktop],
            $website3Id => [EmulatedDevice::Mobile],
        ];

        $i = 0;
        foreach ($settingsEmulatedDevices as $siteId => $settingEmulatedDevices) {
            $siteUrl = Site::getMainUrlFor($siteId);
            if ($siteId === 3) {
                $siteUrl = 'https://www.google.com';
            }

            $settings = new MeasurableSettings($siteId);
            $settingsEmulatedDeviceValue = count($settingEmulatedDevices) == 2 ? EmulatedDevice::Both : end($settingEmulatedDevices);
            $settings->getSetting('emulated_device')->setValue($settingsEmulatedDeviceValue);
            $settings->save();

            $date = Date::factory('2020-06-15');
            $tracker = Fixture::getTracker($siteId, $date->subHour(1)->getDatetime());
            $tracker->setTokenAuth($this->superUserTokenAuth);
            $tracker->setUrl($siteUrl . '/some/test/page');
            $tracker->doTrackPageView('I Am A Robot');
            $idAction = Db::fetchOne('
                SELECT `idaction`
                FROM `' . Common::prefixTable('log_action') . '`
                WHERE `type` = 1
                ORDER BY `idaction` DESC
                LIMIT 1
            ');
            $this->assertNotFalse($idAction);
            $this->assertGreaterThan(0, $idAction);

            $multiplier = pow(10, $i);
            foreach ($settingEmulatedDevices as $settingEmulatedDevice) {
                foreach (Audit::METRICS as $metric) {
                    $metricMinValues = [
                        'firstContentfulPaint' => 200 * $multiplier,
                        'speedIndex' => 200 * $multiplier,
                        'largestContentfulPaint' => 200 * $multiplier,
                        'interactive' => 300 * $multiplier,
                        'totalBlockingTime' => 250 * $multiplier,
                        'cumulativeLayoutShift' => 8 * $multiplier,
                        'score' => 50
                    ];
                    $metricMedianValues = [
                        'firstContentfulPaint' => 400 * $multiplier,
                        'speedIndex' => 500 * $multiplier,
                        'largestContentfulPaint' => 400 * $multiplier,
                        'interactive' => 700 * $multiplier,
                        'totalBlockingTime' => 500 * $multiplier,
                        'cumulativeLayoutShift' => 20 * $multiplier,
                        'score' => 80
                    ];
                    $metricMaxValues = [
                        'firstContentfulPaint' => 800 * $multiplier,
                        'speedIndex' => 1000 * $multiplier,
                        'largestContentfulPaint' => 800 * $multiplier,
                        'interactive' => 1000 * $multiplier,
                        'totalBlockingTime' => 1000 * $multiplier,
                        'cumulativeLayoutShift' => 50 * $multiplier,
                        'score' => 100
                    ];
                    Db::get()->query('
                        INSERT INTO ' . Common::prefixTable('log_performance') . '
                        (`idreport`, `idsite`, `emulated_device`, `idaction`, `key`, `min`, `median`, `max`, `created_at`) VALUES
                        (NULL, ?, ?, ?, ?, ?, ?, ?, ?)
                    ', [$siteId, EmulatedDevice::getIdFor($settingEmulatedDevice), $idAction, $metric, $metricMinValues[$metric], $metricMedianValues[$metric], $metricMaxValues[$metric], Date::factory('2020-06-15')->toString('Y-m-d')]);
                }
            }
            $i++;
        }

        $queryAll = Db::get()->query('SELECT * FROM ' . Common::prefixTable('log_performance'));
        $this->assertEquals(28, Db::get()->rowCount($queryAll));
    }
}
