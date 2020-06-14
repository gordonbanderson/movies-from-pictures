<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Runner;

use Garden\Cli\Args;
use League\CLImate\CLImate;
use Suilven\MoviesFromPictures\Task\CreateVideoTask;
use Suilven\MoviesFromPictures\Task\HashesTask;
use Suilven\MoviesFromPictures\Task\HashGroupingTask;
use Suilven\MoviesFromPictures\Task\ResizeTask;
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
     * @param Args $args
     */
    public function run($args): void
    {
        $this->climate->bold('Make movies from motordrive pics');


        $this->climate->border();
        \error_log('ARGS');
        \var_dump($args);

        $photoDir = $args->getArg('photoDir');
        $photoDir = \rtrim($photoDir, '/');

        switch ($args->getCommand()) {
            case 'hashes':
                $this->climate->out('HASHES');
                $task = new HashesTask($photoDir);
                $task->run();
                exit;
            case 'resize':
                $this->climate->out('RESIZE');
                $task = new ResizeTask($photoDir);
                $task->run();
                exit;
            case 'grouping':
                $this->climate->out('GROUPING');
                $tolerance = $args->getOpt('tolerance', 75);
                $length = $args->getOpt('length', 3);

                $task = new HashGroupingTask($photoDir, $tolerance, $length);
                $task->run();
                exit;
            case 'video':
                $this->climate->out('VIDEO');
                $task = new CreateVideoTask($photoDir);
                $task->run();
                exit;
            default:
                $this->climate->red('No known command was called, help file shown instead');
                echo $options->help();
                exit;
        }
    }
}
