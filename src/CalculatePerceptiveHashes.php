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
use Suilven\Flickr\Helper\FlickrSetHelper;

class CalculatePerceptiveHashes extends BuildTask
{

    protected $title = 'Calculate perceptive hashes for a Flickr Set';

    protected $description = 'Calculate perceptive hashes and store in the database for a given Flickr set';

    protected $enabled = true;

    private static $segment = 'calculate-perceptive-hash';


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $size = isset($_GET['size'])
            ? $_GET['size']
            : 'small';

        $flickrSetID = $_GET['id'];

        $flickrSetHelper = new FlickrSetHelper();
        $flickrSet = $flickrSetHelper->getOrCreateFlickrSet($flickrSetID);

        $this->mkdir_if_required('/tmp/flickr');
        $targetDir = '/tmp/flickr/' . $flickrSetID;
        $this->mkdir_if_required($targetDir);
        $movieDir = $targetDir . '/movie';
        $this->mkdir_if_required($movieDir);

        $srcDir = 'public/flickr/images/' . $flickrSetID;

        $this->calculatePerceptiveHashes($flickrSet, $targetDir, $size);
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
        $counter = 0;
        $total = $flickrSet->FlickrPhotos()->count();

        foreach ($flickrSet->FlickrPhotos()->sort($flickrSet->SortOrder) as $flickrPhoto) {
            $oldHash = $flickrPhoto->PerceptiveHash;
            $counter++;



            if ($flickrPhoto->PerceptiveHash) {
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
            //error_log('Downloading ' . $imageURL . ' of size ' . $size);
            $ch = \curl_init($imageURL);

            $filename = 'tohash.jpg';
            $complete_hash_file_path = \trim($targetDir) . '/' . \trim($filename);
            $complete_hash_file_path = \str_replace(' ', '', $complete_hash_file_path);

            //error_log('CSL: ' . $complete_hash_file_path);

            // @todo This fails if public/flickr/images/FLICKR_SET_ID is missing
            //error_log('TARGET DIR: ' . $targetDir);
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

            \error_log($counter . '/' . $total . '    [' . $hash .']');


            $flickrPhoto->PerceptiveHash = $hash;
            $flickrPhoto->write();
        }
    }
}
