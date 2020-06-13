<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Task;

use League\CLImate\CLImate;
use Suilven\MoviesFromPictures\Database\Connection;

class ResizeTask
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
        $this->mkdirs();
        $this->resizeImagesThumbs();
        $this->resizeImagesHD();
    }


   private function mkdirs()
   {
       $resizedPath = $this->pictureDirectory . '/resized';
       if (!file_exists($resizedPath . '/')) {
           mkdir($resizedPath, 0744);
       }

       $thumbsPath = $this->pictureDirectory . '/thumbs';
       if (!file_exists($thumbsPath . '/')) {
           mkdir($thumbsPath, 0744);
       }
   }

    private function resizeImagesThumbs()
    {
        $output = [];
        $cmd = '/usr/local/bin/imgp -x 128x128 ' . $this->pictureDirectory;
        exec($cmd, $output);
        exec('mv ' . $this->pictureDirectory . '/*_IMGP.jpg ' . $this->pictureDirectory . '/thumbs/');
        exec("rename 's/_IMGP//g' " . $this->pictureDirectory . '/thumbs/*.jpg');
    }

    private function resizeImagesHD()
    {
        $output = [];
        $cmd = '/usr/local/bin/imgp -x 1920x1080 ' . $this->pictureDirectory;
        exec($cmd, $output);
        exec('mv ' . $this->pictureDirectory . '/*_IMGP.jpg ' . $this->pictureDirectory .'/resized/');
        exec("rename 's/_IMGP//g' " . $this->pictureDirectory . '/resized/*.jpg');

    }
}
