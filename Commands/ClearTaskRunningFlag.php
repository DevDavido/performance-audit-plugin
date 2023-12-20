<?php

namespace Piwik\Plugins\PerformanceAudit\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Log\Logger;
use Piwik\Option;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\PerformanceAudit\Tasks;

class ClearTaskRunningFlag extends ConsoleCommand
{
    /**
     * Command configuration.
     *
     * @return void
     */
    protected function doInitialize(): void
    {
        $this->setName('performanceaudit:clear-task-running-flag');
        $this->setDescription('Clear flag for currently running performance audit task');
    }

    /**
     * Execute command.
     *
     * @return int
     */
    protected function doExecute(): int
    {
        $hasRunningTask = !!Option::get(Tasks::hasTaskRunningKey());
        if ($hasRunningTask) {
            StaticContainer::get(Logger::class)->debug('Cleared task running flag manually now');
            Option::delete(Tasks::hasTaskRunningKey());

            $this->getOutput()->writeln('<info>Performance Audit running task flag was cleared successfully.</info>');
            return self::SUCCESS;
        }

        StaticContainer::get(Logger::class)->debug('No task running flag available to clear now');
        $this->getOutput()->writeln('<info>Performance Audit running task flag was not set, so nothing to clear now.</info>');
        return self::SUCCESS;
    }
}
