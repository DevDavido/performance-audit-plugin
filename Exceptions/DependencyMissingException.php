<?php

namespace Piwik\Plugins\PerformanceAudit\Exceptions;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use RuntimeException;

class DependencyMissingException extends RuntimeException
{
    /**
     * DependencyMissingException constructor.
     *
     * @param string $dependency
     * @param array $extraDirs
     */
    public function __construct($dependency = '', $extraDirs = [])
    {
        $message = $dependency . " dependency not found.\n";
        if (count($extraDirs) > 0) {
            $message .= " Searched in the following directories for the dependency: ";
            $message .= implode(", ", $extraDirs) . ".\n";
        }
        if (ini_get('open_basedir')) {
            $searchDirs = array_filter(array_merge(explode(PATH_SEPARATOR, ini_get('open_basedir')), $extraDirs));
            $message .= " Please disable PHP open_basedir or set your PHP open_basedir option to: \n";
            $message .= implode(PATH_SEPARATOR, $searchDirs);
        }
        parent::__construct($message);
    }
}
