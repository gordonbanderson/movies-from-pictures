<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\Flickr\Task;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Suilven\Flickr\Helper\FlickrPerceptiveHashHelper;
use Suilven\Flickr\Helper\FlickrSetHelper;
use Suilven\Sluggable\Helper\SluggableHelper;

class CreateVideoFromPerceptiveHashes extends BuildTask
{

    protected $title = 'Calculate perceptive hashes for a Flickr Set';

    protected $description = 'Calculate perceptive hashes and store in the database for a given Flickr set';

    protected $enabled = true;

    private static $segment = 'create-video-from-perceptive-hash';


    public function findSequences($flickrSet, $srcDir, $targetDir): void
    {
        $helper = new FlickrPerceptiveHashHelper();
        $buckets = $helper->findSequences($flickrSet);

        $html = '';

        $ctr = 0;

        $bucketSize = \count($buckets);

        \error_log('BS: ' . $bucketSize);

        for ($j=0; $j< $bucketSize; $j++) {
            $bucket = $buckets[$j];
            \error_log('BUCKET, OF SIZE ' . \count($bucket));

            \error_log(\print_r($bucket, 1));

            $currentBucketSize = \count($bucket);
            // HACK, had to -1 to get it to work
            for ($i=0; $i<$currentBucketSize; $i++) {
                \error_log('Bucket ' . $i);
                \error_log(\print_r($bucket[$i], 1));
                $html .= "\n<img src='". $bucket[$i]['url']."'/>";

                $filename = \basename($bucket[$i]['url']);
                $from = \trim($srcDir) .'/' . \trim($filename);

                $paddedCtr = \str_pad($ctr, 8, '0', \STR_PAD_LEFT);
                $to = \trim($targetDir) .'/' . $paddedCtr . '.JPG';
                \error_log($from . ' --> ' . $to);
                \copy($from, $to);
                $rotated = $bucket[$i]['rotated'];
                \error_log('>>>> ROTATED: ' . $rotated);
                if ($rotated) {
                    $dimensions = '1365x2048';
                    //$dimensions = '66%x100%';
                    //convert [input file] -resize 1920x1080 -background black -gravity center -extent 1920x1080 [output file]
                    $cmd = ('/usr/bin/convert ' . $to .' -gravity center -background black -extent ' . $dimensions .' ' . $to);
                    \error_log('CMD:' . $cmd);
                    \exec($cmd);
                }

                $ctr++;
            }


            $html .= '<br/><hr/><br/>';
        }

        \file_put_contents('/var/www/buckets.html', $html);
    }


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $size = 'small';

        $flickrSetID = $_GET['id'];

        $flickrSetHelper = new FlickrSetHelper();
        $flickrSet = $flickrSetHelper->getOrCreateFlickrSet($flickrSetID);

        $this->mkdir_if_required('/tmp/flickr');
        $targetDir = '/tmp/flickr/' . $flickrSetID;
        $this->mkdir_if_required($targetDir);
        $movieDir = $targetDir . '/movie';
        $this->mkdir_if_required($movieDir);

        $srcDir = 'public/flickr/images/' . $flickrSetID;

        $this->calculatePerceptiveHashes($flickrSet, $srcDir, $targetDir, $size);
        $this->findSequences($flickrSet, $srcDir, $movieDir);

        $slugHelper = new SluggableHelper();
        $slug = $slugHelper->getSlug($flickrSet->Title);

        $cmd = 'cd /tmp/flickr/' . $flickrSetID . '/movie && ';
        $cmd .= 'mencoder "mf://*.JPG" -mf fps=12 -o /var/www/' . $slug;
        $cmd .= '.avi -ovc lavc -lavcopts vcodec=mpeg4:vbitrate=13660000 && cd -';

        \error_log($cmd);
    }


    private function mkdir_if_required($dir): void
    {
        if (\file_exists($dir) || \is_dir($dir)) {
            return;
        }

        \mkdir($dir);
    }


    private function calculatePerceptiveHashes($flickrSet, $targetDir, $size): void
    {
        \error_log('---- new image ----');
        \error_log('SIZE: ' . $size);

        foreach ($flickrSet->FlickrPhotos()->sort($flickrSet->SortOrder) as $flickrPhoto) {
            $oldHash = $flickrPhoto->PerceptiveHash;


            \error_log('START: hash = ' . $oldHash);
            \error_log('ID: ' . $flickrPhoto->ID);
            if ($flickrPhoto->PerceptiveHash) {
                echo '>>>>> Already calculated hash';

                continue;
            }
            $imageURL = $flickrPhoto->SmallURL;
            switch ($size) {
                case 'original':
                    $imageURL = $flickrPhoto->OriginalURL;

                    break;
                case 'small':
                    $imageURL = $flickrPhoto->SmallURL;

                    break;
                case 'medium':
                    $imageURL = $flickrPhoto->MediumURL;

                    break;
                case 'large':
                    $imageURL = $flickrPhoto->LargeURL;

                    break;
                case 'large1600':
                    $imageURL = $flickrPhoto->Large1600;

                    break;
                default:
                    // url already defaulted
            }
            \error_log('Downloading ' . $imageURL . ' of size ' . $size);
            $ch = \curl_init($imageURL);

            $filename = 'tohash.jpg';
            $complete_hash_file_path = \trim($targetDir) . '/' . \trim($filename);
            $complete_hash_file_path = \str_replace(' ', '', $complete_hash_file_path);

            \error_log('CSL: ' . $complete_hash_file_path);

            // @todo This fails if public/flickr/images/FLICKR_SET_ID is missing
            \error_log('TARGET DIR: ' . $targetDir);
            $fp = \fopen($complete_hash_file_path, 'wb');

            \curl_setopt($ch, \CURLOPT_FILE, $fp);
            \curl_setopt($ch, \CURLOPT_HEADER, 0);
            \curl_exec($ch);
            \curl_close($ch);
            \fclose($fp);

            // @todo Make this configurable
            // tool is avail from https://github.com/commonsmachinery/blockhash-python4, just clone it
            // also required is python-pil

            $hashCMD = 'python3 /var/www/blockhash-python/blockhash.py ' . $complete_hash_file_path;
            $o = \exec($hashCMD, $output);
            $splits = \explode(' ', $o);
            $hash = $splits[0];

/*
            $hash = $hasher->hash($complete_hash_file_path);
*/
            \error_log('Saving hash ' . $hash);

            $flickrPhoto->PerceptiveHash = $hash;
            $flickrPhoto->write();
        }
    }
}
