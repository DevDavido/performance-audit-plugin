<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Plugins\PerformanceAudit\Exceptions\DependencyMissingException;
use Symfony\Component\Process\ExecutableFinder as BaseExecutableFinder;

class ExecutableFinder extends BaseExecutableFinder
{
    /**
     * Searches and finds an executable by name.
     *
     * @param string $name The executable name (without the extension)
     * @return string The executable path
     * @throws DependencyMissingException
     */
    public static function search($name)
    {
        clearstatcache();
        if (is_executable($name)) {
            return $name;
        }

        $extraDirs = array_merge(
            [__DIR__ . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'bin'],
            [__DIR__ . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '.bin'],
            explode(PATH_SEPARATOR, self::getDefaultPath())
        );
        $executablePath = (new parent())->find($name, false, $extraDirs);
        if (!$executablePath) {
            throw new DependencyMissingException($name, $extraDirs);
        }

        return $executablePath;
    }

    /*
    public static function getDefaultComposerHomePath() {
        return ExecutableFinder::isRunningOnWindows() ?
            '%HOMEDRIVE%%HOMEPATH%\AppData\Roaming\Composer' :
            '$HOME/.composer';
    }
    */

    /**
     * Get default path for executables depending on platform.
     *
     * @return string
     */
    public static function getDefaultPath() {
        return ExecutableFinder::isRunningOnWindows() ?
            '%SystemRoot%\system32;%SystemRoot%;%SystemRoot%\System32\Wbem;' :
            '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
    }

    /**
     * Check if running on MS Windows.
     *
     * @return bool
     */
    public static function isRunningOnWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
