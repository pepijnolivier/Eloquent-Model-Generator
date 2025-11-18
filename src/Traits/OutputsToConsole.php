<?php

namespace Pepijnolivier\EloquentModelGenerator\Traits;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\ProgressBar;

trait OutputsToConsole
{

    protected function comment(string $message)
    {
        $consoleOutput = new ConsoleOutput();
        $consoleOutput->writeln($message);
    }

    protected function usingProgressbar(int $max, callable $fn): void
    {
        $consoleOutput = new ConsoleOutput();
        $progressBar = new ProgressBar($consoleOutput, $max);

        $progressBar->start();

        try {
            $fn($progressBar);
        } finally {
            $progressBar->finish();
            $consoleOutput->writeln(''); // New line after progress bar
        }
    }
}
