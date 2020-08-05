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

    /** @var string a number such as 1920x1080 or a string such as HD720, HD1080, UHD */
    private $pictureSize;

    /**
     * HashesTask constructor.
     *
     * @param string $pictureDirectory the relative path to the
     * @param string $size The image size in a format like 1920x1080 or a reference to a name, e.g. HD720, VGA
     */
    public function __construct(string $pictureDirectory, $size = '1920x1080')
    {
        $this->climate = new CLImate();
        $this->pictureDirectory = $pictureDirectory;
        $this->pictureSize = $size;
    }


    public function run(): void
    {
        $this->connection = new Connection($this->pictureDirectory);
        $this->connection->connect();
        $this->mkdirs();
        $this->resizeImagesThumbs();
        $this->resizeImagesHD();
    }


    private function mkdirs(): void
    {
        $resizedPath = $this->pictureDirectory . '/resized';
        if (!\file_exists($resizedPath . '/')) {
            \mkdir($resizedPath, 0744);
        }

        $thumbsPath = $this->pictureDirectory . '/thumbs';
        if (\file_exists($thumbsPath . '/')) {
            return;
        }

        \mkdir($thumbsPath, 0744);
    }


    private function normalizeSize()
    {
        $newSize = $this->pictureSize;
        switch($this->pictureSize) {
            case 'HD720' :
                $newSize = '1280x720';
                break;
            case 'HD1080' :
                $newSize = '1920x1080';
                break;
            case '4K' :
                $newSize = '3840x2160';
                break;
            case 'VGA' :
                $newSize = '640x480';
                break;
            case 'SVGA' :
                $newSize = '800x600';
                break;

                // @todo add more

        }

        $this->pictureSize = $newSize;
        $splits = explode('x', $this->pictureSize);
        $errorMessage = null;
        if (sizeof($splits) !== 2) {
            $errorMessage = 'The size provided, ' . $this->pictureSize .' is not of the format nWIDTHxnHEIGHT';
        } else {
            $width = $splits[0];
            $height = $splits[1];
            if (!is_numeric($width)) {
                $errorMessage = 'The width provided, ' . $width . ', is not a number';
            } else if (!is_numeric($height)) {
                $errorMessage = 'The height provided, ' . $height . ', is not a number';
            }
        }
    }


    private function resizeImagesThumbs(): void
    {
        $output = [];
        $cmd = '/usr/local/bin/imgp -x 128x128 ' . $this->pictureDirectory;
        \exec($cmd, $output);
        \exec('mv ' . $this->pictureDirectory . '/*_IMGP.jpg ' . $this->pictureDirectory . '/thumbs/');
        \exec("rename 's/_IMGP//g' " . $this->pictureDirectory . '/thumbs/*.jpg');
    }


    private function resizeImagesHD(): void
    {
        $output = [];
        $cmd = '/usr/local/bin/imgp -x ' . $this->pictureSize . ' ' . $this->pictureDirectory;
        \exec($cmd, $output);
        \exec('mv ' . $this->pictureDirectory . '/*_IMGP.jpg ' . $this->pictureDirectory .'/resized/');
        \exec("rename 's/_IMGP//g' " . $this->pictureDirectory . '/resized/*.jpg');
    }
}
