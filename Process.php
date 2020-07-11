<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Symfony\Component\Process\Process as BaseProcess;

class Process extends BaseProcess
{
    /**
     * Process constructor with own custom settings.
     *
     * @param array $command
     * @param array|null $env
     */
    public function __construct($command, $env = null)
    {
        $userPath = ExecutableFinder::getDefaultPath();
        if (is_null($env)) {
            $env = [
                'PATH' => $userPath
            ];
        } elseif (is_array($env)) {
            $env += [
                'PATH' => $userPath
            ];
        }

        parent::__construct($command, __DIR__, $env, null, 60);
    }
}
