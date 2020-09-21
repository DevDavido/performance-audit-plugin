<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit\Commands;

use Piwik\Log;
use Piwik\Option;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\PerformanceAudit\Tasks;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearTaskRunningFlag extends ConsoleCommand
{
    /**
     * Command configuration.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('performanceaudit:clear-task-running-flag');
        $this->setDescription('Clear flag for currently running performance audit task');
    }

    /**
     * Execute command.
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hasRunningTask = !!Option::get(Tasks::hasTaskRunningKey());
        if ($hasRunningTask) {
            Log::debug('Cleared task running flag manually now');
            Option::delete(Tasks::hasTaskRunningKey());

            $output->writeln('<info>Performance Audit running task flag was cleared successfully.</info>');
            return;
        }

        Log::debug('No task running flag available to clear now');
        $output->writeln('<info>Performance Audit running task flag was not set, so nothing to clear now.</info>');
    }
}
