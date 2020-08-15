<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Piwik\Plugins\PerformanceAudit\Exceptions\DirectoryNotWriteableException;

class Helper
{
    /**
     * Check if certain directories are writeable.
     *
     * @param array $directories
     * @return void
     * @throws DirectoryNotWriteableException
     */
    public static function checkDirectoriesWriteable(array $directories)
    {
        clearstatcache();
        foreach ($directories as $directory) {
            $directoryPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $directory);
            if (!is_writable($directoryPath)) {
                throw new DirectoryNotWriteableException($directoryPath . ' needs to be a writeable directory.');
            }
        }
    }
}
