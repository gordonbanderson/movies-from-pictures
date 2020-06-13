<?php


namespace Suilven\MoviesFromPictures\Runner;


use League\CLImate\CLImate;
use splitbrain\phpcli\Options;
use Suilven\MoviesFromPictures\Task\HashesTask;
use Suilven\MoviesFromPictures\Terminal\TerminalHelper;

class Runner
{
    use TerminalHelper;

    /** @var \League\CLImate\CLImate */
    private $climate;

    public function __construct()
    {
        $this->climate = new CLImate();
        $this->climate->clear();
    }


    /**
     * @param Options $options
     */
    public function run($options)
    {
        $this->climate->bold('Make movies from motordrive pics');

        $this->climate->black()->bold('COMMANDS:');
        $this->climate->green($options->getCmd());

        $this->climate->border();
        error_log('ARGS');
        var_dump($options->getArgs());

        $photoDir = $options->getArgs()[0];
        $photoDir = rtrim($photoDir, '/');

        switch ($options->getCmd()) {
            case 'hashes':
                $this->climate->warning('HASHES');
                $task = new HashesTask($photoDir);
                $task->run();
                exit;
            default:
                $this->climate->red('No known command was called, help file shown instead');
                echo $options->help();
                exit;
        }
    }
}