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

class CreatePerceptiveHashBuckets extends BuildTask
{

    protected $title = 'Create buckets for a flickr set based on perceptive hash';

    protected $description = 'Create buckets based on perceptive hash';

    protected $enabled = true;

    private static $segment = 'buckets-from-perceptive-hash';

    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $flickrSetID = $_GET['id'];

        $flickrSetHelper = new FlickrSetHelper();
        $flickrSet = $flickrSetHelper->getOrCreateFlickrSet($flickrSetID);

        $pHashHelper = new FlickrPerceptiveHashHelper();
        $bucketsArrary = $pHashHelper->findSequences($flickrSet);
        \print_r($bucketsArrary);

        $buckets = $flickrSet->FlickrBuckets();
        \error_log($buckets->Count());
        if ($buckets->Count() !== 0) {
            return;
        }
    }
}
