<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Task;

use League\CLImate\CLImate;
use Suilven\MoviesFromPictures\Database\Connection;

class HashesTask
{
    /** @var \League\CLImate\CLImate */
    private $climate;

    /** @var \Suilven\MoviesFromPictures\Database\Connection */
    private $connection;

    /** @var string */
    private $pictureDirectory;

    /**
     * HashesTask constructor.
     *
     * @param string $pictureDirectory the relative path to the
     */
    public function __construct(string $pictureDirectory)
    {
        $this->climate = new CLImate();
        $this->pictureDirectory = $pictureDirectory;
    }


    public function run(): void
    {
        $this->connection = new Connection($this->pictureDirectory);
        $this->connection->connect();
        $this->addPhotos();
        $this->calculateHashes();
    }


    private function addPhotos(): void
    {
        $path = $this->pictureDirectory . '/*.jpg';
        $this->climate->info('PATH: ' . $path);
        $files = \glob($path);
        foreach ($files as $file) {
            #$this->climate->info($file);
            $this->connection->insertPhoto($file);
        }
    }


    private function calculateHashes(): void
    {
        $photos = $this->connection->getPhotos();

        $this->climate->border();
        foreach ($photos as $photo) {
            $id = $photo['id'];


            $cmd = '/usr/local/bin/blockhash.py ' . $this->pictureDirectory . '/' . $photo['filename'];
            $this->climate->info($cmd);
            $output = [];
            exec($cmd, $output);
            error_log(print_r($output, true));
            $hashAndFile = $output[0];
            $splits = explode(' ', $hashAndFile);
            $hash = $splits[0];
            error_log('HASH: ' . $hash);
        }
    }
}
