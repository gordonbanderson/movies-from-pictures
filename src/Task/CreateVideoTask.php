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
        $meltCMD = 'xvfb-run -a melt -consumer avformat:timelapse.avi vcodec=libxvid b=5000k';

        // @todo Fix, needs to be under picture dir
        $yaml = file_get_contents(getcwd() .'/video.yml');
        $sequence = Yaml::parse($yaml);

        error_log('SEQUENCE');

        $sequenceNumber = 1;



        // @todo Add alternatives such as info card later
        foreach($sequence as $row)
        {

            $keys = array_keys($row);
            error_log(print_r($keys, true));

            $photosTXT = "";

            foreach($keys as $key)
            {
                switch($key) {
                    case 'bucket':
                        foreach($row['bucket'] as $photo)
                        {
                            error_log('SINGLE PHOTO');
                            error_log(print_r($photo, true));


                            $photosTXT .= $this->pictureDirectory . '/resized/' . $photo['filename'];
                            $photosTXT .= "\n";
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

                        $cardFile = 'card_' . str_pad($sequenceNumber . '', 5, '0',
                                STR_PAD_LEFT)  . '.png';

                        $meltCMD .= ' ' . $cardFile . ' length=50' ;

                        // cant use fade for the first card
                        if ($sequenceNumber > 1) {
                            $meltCMD .= ' -mix 4 -mixer luma';
                        }
                        break;
                }
            }


            echo $photosTXT;

            // @todo Fix path
            file_put_contents(getcwd() . '/photos.txt', $photosTXT);

            // @todo Fix path
            $bucketFile = 'bucket_' . str_pad($sequenceNumber . '', 5, '0', STR_PAD_LEFT)  . '.avi';
            $cmd = 'mencoder "mf://@' . getcwd() . '/photos.txt" -mf fps=12 -o ' . $bucketFile . ' -vf scale=1920:1080 -ovc lavc -lavcopts vcodec=mpeg4:vbitrate=13660000';
            exec($cmd);
            $meltCMD .= ' ' . $bucketFile;
            if ($sequenceNumber > 1) {
                $meltCMD .= ' -mix 2 -mixer luma';
            }

            $sequenceNumber++;


        }

        $this->climate->border();
        $this->climate->info($meltCMD);
        $this->climate->border();

        file_put_contents('makevideo', $meltCMD);

        exec($meltCMD);
    }

}
