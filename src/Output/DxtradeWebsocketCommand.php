<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Output;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DxtradeWebsocketCommand
{
    protected OutputInterface $output;

    protected ?Command $command = null;

    public function __construct()
    {
        $this->output = new ConsoleOutput();
    }

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    /**
     * Write an info message to the console
     */
    public function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            $this->output->writeln("<info>{$message}</info>");
        }
    }

    /**
     * Write an error message to the console
     */
    public function error(string $message): void
    {
        if ($this->command) {
            $this->command->error($message);
        } else {
            $this->output->writeln("<error>{$message}</error>");
        }
    }

    /**
     * Write a comment message to the console
     */
    public function comment(string $message): void
    {
        if ($this->command) {
            $this->command->comment($message);
        } else {
            $this->output->writeln("<comment>{$message}</comment>");
        }
    }

    /**
     * Write a line to the console
     */
    public function line(string $message): void
    {
        if ($this->command) {
            $this->command->line($message);
        } else {
            $this->output->writeln($message);
        }
    }
}
