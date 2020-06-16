<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Task;

use League\CLImate\CLImate;
use Suilven\MoviesFromPictures\Database\Connection;
use Suilven\MoviesFromPictures\Terminal\TerminalHelper;

class HashesTask
{

    use TerminalHelper;

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
        $ctr = 0;
        $amount = \sizeof($photos);
        $this->borderedTitle('Calculationg hash for ' . $amount . ' images');
        $progress = $this->climate->progress()->total($amount);
        foreach ($photos as $photo) {
            $id = (int) $photo['id'];
            // @phpstan-ignore-next-line @todo Fix this
            if (\is_null($photo['hash'])) {
                $cmd = '/usr/local/bin/blockhash.py ' . $this->pictureDirectory . '/thumbs/' . $photo['filename'];
                $output = [];
                \exec($cmd, $output);
                $hashAndFile = $output[0];
                $splits = \explode(' ', $hashAndFile);
                $hash = $splits[0];
                $this->connection->updateHash($id, $hash);
            }

            $ctr++;
            $progress->current($ctr);
        }
    }
}
