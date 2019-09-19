<?php

namespace LittleGiant\SpinDB\Storage;

use Aws\Result;
use Aws\S3\S3Client;
use Exception;
use Injector;
use LittleGiant\SpinDB\Configuration\RotateConfig;
use SS_Object;

class RotateStorage extends SS_Object
{
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
            'Bucket' => $this->getBucket(),
            'Prefix' => $prefix,
        ]);
        $files = [];
        $objects = $objects->get('Contents');

        if (!empty($objects)) {

            foreach ($objects as $object) {
                $parts = RotateConfig::parse($object['Key']);
                if ($parts) {
                    $files[] = new DBBackup($parts);
                }
            }
        }
        return $files;
    }

    /**
     * Add a new backup to the store
     *
     * @param string $localPath Local path containing the file
     * @param string $key       Key to use
     * @return Result
     * @throws Exception
     */
    public function saveFile($localPath, $key)
    {
        // put file into bucket
        $stream = fopen($localPath, 'r');
        try {
            return $this->getClient()->putObject([
                'Bucket' => $this->getBucket(),
                'Key'    => $key,
                'Body'   => $stream,
            ]);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Remove object by key
     *
     * @param string $key
     * @throws Exception
     */
    public function deleteFile($key)
    {
        $this->getClient()->deleteObject([
            'Bucket' => $this->getBucket(),
            'Key'    => $key,
        ]);
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getBucket()
    {
        $bucket = RotateConfig::bucket();
        if (empty($bucket)) {
            throw new Exception("No S3 Bucket provided");
        }
        return $bucket;
    }
}
