<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\CardMaker;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;


class ExampleCard
{

    private $title;

    private $message;

    private $number;

    public function __construct()
    {
        $this->manager = new ImageManager(['driver' => 'imagick']);

    }

    public function setTitle($newTitle)
    {
        $this->title = $newTitle;
    }

    public function setMessage($newMessage)
    {
        $this->message = $newMessage;
    }

    public function setNumber($newNumber)
    {
        $this->number = $newNumber;
    }


    public function generate()
    {
        /** @var ImageManagerStatic $canvas */
        $img = $this->manager->canvas(1920, 1080, '#000099');
        $img->insert('./gradient_srt.png', '',0,0);


        $this->writeText($img, $this->title, 970,100, 96);
        $this->writeText($img, $this->message, 970,800, 48);

        $cardFile = 'card_' . str_pad($this->number . '', 5, '0', STR_PAD_LEFT)  . '.png';

        $img->save($cardFile);

        /*
         *
         *
         *
         *
         * xvfb-run -a melt -consumer avformat:timelapse.avi vcodec=libxvid b=5000k card_00001.png ttl=50 bucket_00001.avi bucket_00002.avi -mix 2 -mixer luma bucket_00003.avi -mix 2 -mixer luma bucket_00004.avi -mix 2 -mixer luma card_00005.png ttl=50 bucket_00005.avi -mix 2 -mixer luma bucket_00006.avi -mix 2 -mixer luma bucket_00007.avi -mix 2 -mixer luma bucket_00008.avi -mix 2 -mixer luma bucket_00009.avi -mix 2 -mixer luma bucket_00010.avi -mix 2 -mixer luma bucket_00011.avi -mix 2 -mixer luma bucket_00012.avi -mix 2 -mixer luma bucket_00013.avi -mix 2 -mixer luma bucket_00014.avi -mix 2 -mixer luma
convert -size 1920x1080 gradient: -rotate -45 \
          -gravity center -crop 1920x1080+0+0 +repage \
          gradient_diagonal.png

        $img->insert('public/aucc-bg-2.jpg', '',0,0);
       // $img->greyscale()->blur(5);
      //  $img->colorize(100, 38, 75);


        $this->writeText($img, $matchHeading, 970,$this->padding, 48);
        $this->writeText($img, $matchByline, 970,$this->padding + $this->fontSize * 1.2, 36);

         // 248x89
        $img->insert('themes/ssclient-core-theme/dist/img/furniture/sponsor-tailend-teamsheet.png',
            '',
            1920-248-$this->padding,
            1080-89-$this->padding
        );

         */


    }


    /**
     * @param Image $img
     * @param string $text
     * @param int $x
     * @param int $y
     * @param int $fontSize
     *
     */
    private function writeText($img, $text, $x, $y, $fontSize = 10)
    {
        $this->fontSize = $fontSize;
        $img->text($text, $x, $y, function ($font) {
            // $font->file('foo/bar.ttf');
            // @todo Make this configurable
            $font->file('./Roboto-Medium.ttf');
            $font->size($this->fontSize);
            $font->color('#fff');
            $font->align('center');
            $font->valign('top');
            //$font->angle(45);
        });
    }


}
