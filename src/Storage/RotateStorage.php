<?php

namespace LittleGiant\SpinDB\Storage;

use Aws\S3\S3Client;
use Exception;
use LittleGiant\SpinDB\Configuration\RotateConfig;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

class RotateStorage
{
    use Injectable;

    /**
     * s3 client
     *
     * @var S3Client
     */
    protected $client;

    /**
     * @return S3Client
     */
    protected function getClient()
    {
        if ($this->client) {
            return $this->client;
        }
        $this->client = Injector::inst()->get(S3Client::class . '.spindb');
        return $this->client;
    }

    /**
     * Get all folder
     *
     * @return string
     * @throws Exception
     */
    protected function getPathFolder()
    {
        $path = RotateConfig::path();

        // Keep joining all parent paths until we get something with a wildcard still in it
        $parts = [];
        $next = strtok($path, '/');
        while ($next && strpos($next, '{') === false) {
            $parts[] = $next;
            $next = strtok('/');
        }

        return implode('/', $parts);
    }

    /**
     * Get the list of documents on the server
     *
     * @return DBBackup[]
     * @throws Exception
     */
    public function getFiles()
    {
        $client = $this->getClient();
        $prefix = $this->getPathFolder();
        $objects = $client->listObjects([
            'Bucket' => RotateConfig::bucket(),
            'Prefix' => $prefix,
        ]);
        $files = [];
        foreach ($objects->get('Contents') as $object) {
            $parts = RotateConfig::parse($object['Key']);
            if ($parts) {
                $files[] = new DBBackup($parts);
            }
        }
        return $files;
    }
}
