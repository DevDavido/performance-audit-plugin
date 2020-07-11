<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Ducks\Component\SplTypes\SplEnum;
use OutOfBoundsException;

class EmulatedDevice extends SplEnum
{
    /**
     * Default emulated device.
     *
     * @var string
     */
    public const __default = self::Mobile;

    /**
     * Emulated devices.
     *
     * @var string
     */
    public const Desktop = 'desktop';

    /** @var string */
    public const Mobile = 'mobile';

    /** @var string */
    public const Both = 'both';

    /**
     * Lookup array for devices.
     *
     * @var array
     */
    private const Lookup = [
        self::Mobile => 1,
        self::Desktop => 2,
        self::Both => 3
    ];

    /**
     * Get ID of emulated device.
     *
     * @param string $enum
     * @return int
     * @throws OutOfBoundsException
     */
    public static function getIdFor(string $enum) {
        if (!isset(self::Lookup[$enum])) {
            throw new OutOfBoundsException($enum . ' is no valid EmulatedDevice enum.');
        }

        return self::Lookup[$enum];
    }

    /**
     * Get emulated device(s) as array.
     *
     * @param string $enum
     * @return array
     */
    public static function getList(string $enum) {
        $emulatedDevices = (array) $enum;
        if (current($emulatedDevices) === 'both') {
            $emulatedDevices = ['desktop', 'mobile'];
        }

        return $emulatedDevices;
    }
}
