<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Output;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DxtradeWebsocketCommand
{
    protected OutputInterface $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput();
    }

    /**
     * Write an info message to the console
     */
    public function info(string $message): void
    {
        $this->output->writeln("<info>{$message}</info>");
    }

    /**
     * Write an error message to the console
     */
    public function error(string $message): void
    {
        $this->output->writeln("<error>{$message}</error>");
    }

    /**
     * Write a comment message to the console
     */
    public function comment(string $message): void
    {
        $this->output->writeln("<comment>{$message}</comment>");
    }

    /**
     * Write a line to the console
     */
    public function line(string $message): void
    {
        $this->output->writeln($message);
    }
}
