<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Terminal;

trait TerminalHelper
{
    /**
     * Render a green tick in the terminal
     */
    private function tick(): void
    {
        $this->climate->bold()->green('✓');
    }


    /**
     * Render a red cross in the terminial
     */
    private function cross(): void
    {
        $this->climate->bold()->red('✘');
    }


    private function taskReport(string $message, int $retVal = 0): void
    {
        $this->climate->inline($message . '  ');
        if ($retVal !== 0) {
            $this->cross();
        } else {
            $this->tick();
        }
    }
}
