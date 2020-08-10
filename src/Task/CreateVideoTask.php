<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Task;

use League\CLImate\CLImate;
use Suilven\MoviesFromPictures\CardMaker\ExampleCard;
use Suilven\MoviesFromPictures\Database\Connection;
use Suilven\MoviesFromPictures\Terminal\TerminalHelper;
use Symfony\Component\Yaml\Yaml;

class CreateVideoTask
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
        $this->makeVideo();
    }


    private function makeVideo()
    {
        $bg = './bg.jpg';

        // taken from https://www.youtube.com/watch?v=3PRZ9L_KLdI&list=PLcUid3OP_4OWC-GJ6KfHK7dIK_yRKKn0e&index=4&t=0s,
        // 11m 34s
        $meltCMD = 'xvfb-run -a melt -consumer avformat:output/timelapse.avi vcodec=libxvid b=5000k';

        // @todo Fix, needs to be under picture dir
        $yaml = file_get_contents(getcwd() .'/output/video.yml');
        $sequence = Yaml::parse($yaml);

        error_log('SEQUENCE');

        $sequenceNumber = 1;



        // @todo Add alternatives such as info card later
        foreach($sequence as $row)
        {

            $keys = array_keys($row);
            error_log(print_r($keys, true));

            $photosTXT = "";

            $isVertical = false;
            $width = 0;
            $height = 0;

            foreach($keys as $key)
            {
                switch($key) {
                    case 'bucket':
                        $ctr = 0;
                        foreach($row['bucket'] as $photo)
                        {
                            $ctr++;
                            error_log('SINGLE PHOTO ' . $ctr);
                            error_log(print_r($photo, true));

                            $photoFile = $this->pictureDirectory . '/resized/' . $photo['filename'];

                            $photosTXT .= $photoFile;
                            $photosTXT .= "\n";

                            // all images should be the same orientation and size, as such only get this info for
                            // the first image
                            if ($ctr === 1) {
                                $width = $this->getWidth($photoFile);
                                $height = $this->getHeight($photoFile);
                                $isVertical = $this->isVertical($photoFile);
                                if ($isVertical) {
                                    $temp = $width;
                                    $width = $height;
                                    $height = $temp;
                                }
                            }
                        }
                        break;

                    case 'card':
                        $card = new ExampleCard();
                        error_log('CARD!!!!');
                        error_log(print_r($row, true));

                        $cardInfo = $row['card'];
                        error_log('CARD INFO: ' . print_r($cardInfo, true));
                        $title = $cardInfo['title'];
                        $message = $cardInfo['message'];
                        error_log('TITLE T1: ' . $title);
                        error_log('MESSAGE T1: ' . $message);
                        $card->setTitle($title);
                        $card->setMessage($message);
                        $card->setNumber($sequenceNumber);
                        $card->generate();

                        $cardFile = getcwd() . '/output/card_' . str_pad($sequenceNumber . '', 5, '0',
                                STR_PAD_LEFT)  . '.png';

                        $meltCMD .= ' ' . $cardFile . ' length=50' ;

                        // cant use fade for the first card
                        if ($sequenceNumber > 1) {
                            $meltCMD .= ' -mix 4 -mixer luma';
                        }
                        break;
                }
            }


            $this->climate->border('****^');
            $this->climate->info($photosTXT);
            $this->climate->border();

            $this->climate->underline($width . ' x ' . $height .', V=' . $isVertical);
            $photosTXT = trim($photosTXT);

            // @todo Fix path
            file_put_contents(getcwd() . '/output/photos.txt', $photosTXT);

            error_log('SIZE: ' . strlen($photosTXT));

            if (strlen($photosTXT) > 0) {
                // @todo Fix path
                $bucketFile = './output/bucket_' . str_pad($sequenceNumber . '', 5, '0', STR_PAD_LEFT)  . '.avi';
                $cmd = 'mencoder "mf://@' . getcwd() . '/output/photos.txt" -mf fps=12 -o ' . $bucketFile . ' -vf scale=1920:1620 -ovc lavc -lavcopts vcodec=mpeg4:vbitrate=13660000';
                $this->climate->border();
                $this->climate->blue($cmd);
                $this->climate->border();
                exec($cmd);

                if ($isVertical) {
                    $cmd = 'ffmpeg -i ' . $bucketFile .' -vf "transpose=2" output/rotated.avi';
                    exec($cmd);
                    exec('mv output/rotated.avi ' . $bucketFile);
                }
                $meltCMD .= ' ' . $bucketFile;
                if ($sequenceNumber > 1) {
                    $meltCMD .= ' -mix 2 -mixer luma';
                }
            }

            $sequenceNumber++;
        }

        $this->climate->border();
        $this->climate->info($meltCMD);
        $this->climate->border();

        file_put_contents('makevideo', $meltCMD);

        exec($meltCMD);
    }


    private function getWidth($relativeFilePath)
    {
        $cmd = 'exiftool ' . $relativeFilePath . " | grep 'Image Width' | tail -1";
        $output = shell_exec($cmd);
        $splits = explode(':', $output);
        $width = intval($splits[1]);
        return $width;
    }

    private function getHeight($relativeFilePath)
    {
        $cmd = 'exiftool ' . $relativeFilePath . " | grep 'Image Height' | tail -1";
        $output = shell_exec($cmd);
        $splits = explode(':', $output);
        $height = intval($splits[1]);
        return $height;
    }


    private function isVertical($relativeFilePath)
    {
        error_log('**** VERTICAL CHECK ****');
        $cmd = 'exiftool ' . $relativeFilePath . " | grep 'Camera Orientation'";
        $output = shell_exec($cmd);
        $splits = explode(':', $output);
        $info = ($splits[1]);
        $start = substr($info, 0,11);
        error_log('S=*' . $start . '*');
        return $start !== ' Horizontal';
    }

}
