<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Task;

use League\CLImate\CLImate;
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
        // @todo Fix, needs to be under picture dir
        $yaml = file_get_contents(getcwd() .'/video.yml');
        $sequence = Yaml::parse($yaml);
        $photosTXT = "";

        error_log('SEQUENCE');

        // @todo Add alternatives such as info card later
        foreach($sequence as $row)
        {

            error_log('ROW KEYS:' . $row);
            $keys = array_keys($row);
            error_log(print_r($keys, true));

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
                }
            }



        }

        echo $photosTXT;

        // @todo Fix path
        file_put_contents(getcwd() . '/photos.txt', $photosTXT);

        /**
         * $cmd .= 'mencoder "mf://*.JPG" -mf fps=12 -o /var/www/' . $slug;
        $cmd .= '.avi -ovc lavc -lavcopts vcodec=mpeg4:vbitrate=13660000 && cd -';
         */

        // @todo Fix path
        $cmd = 'mencoder "mf://@' . getcwd() . '/photos.txt" -mf fps=12 -o timelapse_video.avi -vf scale=1920:1080 -ovc lavc -lavcopts vcodec=mpeg4:vbitrate=13660000';
        exec($cmd);

    }

}
