<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Task;

use League\CLImate\CLImate;
use Suilven\MoviesFromPictures\Database\Connection;
use Suilven\MoviesFromPictures\Terminal\TerminalHelper;
use Symfony\Component\Yaml\Yaml;

class HashGroupingTask
{

    use TerminalHelper;

    /** @var \League\CLImate\CLImate */
    private $climate;

    /** @var \Suilven\MoviesFromPictures\Database\Connection */
    private $connection;

    /** @var string */
    private $pictureDirectory;

    private $tolerance;

    private $length;

    /**
     * HashesTask constructor.
     *
     * @param string $pictureDirectory the relative path to the
     */
    public function __construct(string $pictureDirectory, $tolerance, $length)
    {
        $this->climate = new CLImate();
        $this->pictureDirectory = $pictureDirectory;
        $this->tolerance = $tolerance;
        $this->length = $length;

        \error_log('LENGTH: ' . $this->length);
        \error_log('TOLERANCE: ' . $this->tolerance);
    }


    public function run(): void
    {
        $this->connection = new Connection($this->pictureDirectory);
        $this->connection->connect();
        $buckets = $this->groupByHash();
        \error_log(\print_r($buckets, true));
        $this->generateHTML($buckets);
        $this->generateYAML($buckets);
    }


    private function generateYAML($buckets): void
    {
        $bucketsWithoutCards = [];
        foreach ($buckets as $bucket) {
            $yamlBucket = ['bucket' => $bucket];
            $bucketsWithoutCards[] = $yamlBucket;
        }
        $yaml = Yaml::dump($bucketsWithoutCards, Yaml::DUMP_OBJECT_AS_MAP);
        \file_put_contents('video.yml', $yaml);
    }


    private function generateHTML($buckets): void
    {
        \error_log('BUCKET SIZE: ' . \sizeof($buckets));

        $loader = new \Twig\Loader\FilesystemLoader(\getcwd() . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/twig',
        ]);


        $html = $twig->render('index.html.twig', [
            'title' => 'Buckets List',
            'buckets' => $buckets,
            'pictureDirectory' => $this->pictureDirectory,
        ]);

        $path = $this->pictureDirectory . '/grouping.html';
        \file_put_contents($path, $html);
    }


    /** @return array */
    private function groupByHash(): array
    {
        $this->borderedTitle('Grouping by Perceptive Hash');

        $photos = $this->connection->getPhotos();

        $currentBucket = [];
        $buckets = [];
        for ($i = 1; $i < \count($photos) - 1; $i++) {
            $id = $photos[$i]['id'];

            $this->climate->info('Checking file ' . $photos[$i]['filename']);

            // if the bucket is empty, start with the current image
            if (\sizeof($currentBucket) === 0) {
                $currentBucket[] = [
                    'id' => $id,
                    'filename' => $photos[$i]['filename'],
                   // 'rotated' => $hashes[$i]['Rotated']
                ];
            }
            $hash0 = $photos[$i]['hash'];
            $hash1 = $photos[$i + 1]['hash'];
            $distance = $this->hammingDist($hash0, $hash1);

            $this->climate->info($i . ': D=' . $distance);

            // if we are within tolerance, add to the bucket
            if ($distance < $this->tolerance) {
                $currentBucket[] = [
                    'id' => $photos[$i+1]['id'],
                    'filename' => $photos[$i+1]['filename'],
                    //'rotated' => $photos[$i]['Rotated']
                ];
            } else {
                // we need to save the current bucket if it's long enough
                if (\sizeof($currentBucket) < $this->length) {
                    $this->climate->blue('Bucket created but is too short');
                } else {
                    $this->climate->blue('Adding bucket of size ' . \sizeof($currentBucket));
                    $buckets[] = $currentBucket;
                }
                $currentBucket = [];
            }
        }

        // add the last bucket if it's long enough
        if (\sizeof($currentBucket) < $this->length) {
            \error_log('Bucket created but is too short');
        } else {
            \error_log('Adding bucket');
            $buckets[] = $currentBucket;
        }

        // optimal_bitrate = 50 * 25 * 2048 * 1366 / 256
        // mencoder "mf://*.JPG" -mf fps=12 -o test.avi -ovc lavc -lavcopts vcodec=mpeg4:vbitrate=13660000
        return $buckets;
    }


    /**
     * @param string $hash1 a hash in hexadecimal format
     * @param string $hash2 a hash in hexadecimal format
     * @return int the hamming distance between the hashes
     */
    private function hammingDist(string $hash1, string $hash2): int
    {
        $binaryHash1 = $this->hexHashToBinary($hash1);
        $binaryHash2 = $this->hexHashToBinary($hash2);

        $i = 0;
        $count = 0;
        while (isset($binaryHash1[$i])) {
            if ($binaryHash1[$i] !== $binaryHash2[$i]) {
                $count++;
            }
            $i++;
        }

        return $count;
    }


    /**
     * @param string $hash a hexidecimal hash in lowercase
     * @return string a binary string of 1s and 0s
     */
    private function hexHashToBinary(string $hash): string
    {
        $binaryHash = \str_replace('0', '0000', $hash);
        $binaryHash = \str_replace('1', '0001', $binaryHash);
        $binaryHash = \str_replace('2', '0010', $binaryHash);
        $binaryHash = \str_replace('3', '0011', $binaryHash);
        $binaryHash = \str_replace('4', '0100', $binaryHash);
        $binaryHash = \str_replace('5', '0101', $binaryHash);
        $binaryHash = \str_replace('6', '0110', $binaryHash);
        $binaryHash = \str_replace('7', '0111', $binaryHash);
        $binaryHash = \str_replace('8', '1000', $binaryHash);
        $binaryHash = \str_replace('9', '1001', $binaryHash);
        $binaryHash = \str_replace('a', '1010', $binaryHash);
        $binaryHash = \str_replace('b', '1011', $binaryHash);
        $binaryHash = \str_replace('c', '1100', $binaryHash);
        $binaryHash = \str_replace('d', '1101', $binaryHash);
        $binaryHash = \str_replace('e', '1110', $binaryHash);
        $binaryHash = \str_replace('f', '1111', $binaryHash);

        return $binaryHash;
    }
}
