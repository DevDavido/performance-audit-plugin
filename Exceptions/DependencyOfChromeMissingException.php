<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit\Exceptions;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use RuntimeException;

class DependencyOfChromeMissingException extends RuntimeException
{
    const CHROME_DEPENDENCIES = [
        'gconf-service', 'libasound2', 'libatk1.0-0', 'libc6', 'libcairo2',
        'libcups2', 'libdbus-1-3', 'libexpat1', 'libfontconfig1', 'libgbm-dev',
        'libgcc1', 'libgconf-2-4', 'libgdk-pixbuf2.0-0', 'libglib2.0-0', 'libgtk-3-0',
        'libnspr4', 'libpango-1.0-0', 'libpangocairo-1.0-0', 'libstdc++6', 'libx11-6',
        'libx11-xcb1', 'libxcb1', 'libxcomposite1', 'libxcursor1', 'libxdamage1',
        'libxext6', 'libxfixes3', 'libxi6', 'libxrandr2', 'libxrender1',
        'libxss1', 'libxtst6', 'ca-certificates', 'fonts-liberation',
        'libappindicator1', 'libnss3', 'lsb-release', 'xdg-utils', 'wget'
    ];

    /**
     * DependencyOfChromeMissingException constructor.
     */
    public function __construct()
    {
        $message = 'In order to run this plugin which needs headless Chrome and its software package dependencies, ' .
            'you need to make sure the following software dependencies are installed on your web server: ' .
            implode(' ', self::CHROME_DEPENDENCIES);
        parent::__construct($message);
    }
}
