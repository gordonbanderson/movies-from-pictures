<?php


namespace Suilven\MoviesFromPictures\Task;


use League\CLImate\CLImate;
use splitbrain\phpcli\Options;
use Suilven\MoviesFromPictures\Database\Connection;
use Suilven\MoviesFromPictures\Terminal\TerminalHelper;

class HashesTask
{
    /** @var \League\CLImate\CLImate */
    private $climate;

    /** @var Connection */
    private $connection;

    /** @var string */
    private $pictureDirectory;

    public function __construct($pictureDirectory)
    {
        $this->climate = new CLImate();
        $this->pictureDirectory = $pictureDirectory;
    }

    public function run()
    {
        $this->connection = new Connection();
        $this->connection->connect($this->pictureDirectory);
        $this->addPhotos();
        $this->calculateHashes();
    }


    private function addPhotos()
    {
        $path = $this->pictureDirectory . '/*.jpg';
        $this->climate->info('PATH: ' . $path);
        $files =  glob($path);
        foreach($files as $file) {
            #$this->climate->info($file);
            $this->connection->insertPhoto($file);
        }
    }


    private function calculateHashes()
    {
        $photos = $this->connection->getPhotos();

        $this->climate->border();
        foreach($photos as $photo)
        {
            //$this->climate->info(print_r($photo, 1));
        }

    }



}