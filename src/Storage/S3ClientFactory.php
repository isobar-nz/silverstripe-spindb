<?php

namespace LittleGiant\SpinDB\Storage;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use LittleGiant\SpinDB\Configuration\RotateConfig;
use SilverStripe\Framework\Injector\Factory;

class S3ClientFactory implements Factory
{
    const VERSION = '2006-03-01';

    /**
     * Creates a new service instance.
     *
     * @param string $service The class name of the service.
     * @param array  $params The constructor parameters.
     * @return S3Client The created service instances.
     */
    public function create($service, array $params = array())
    {
        // Sign upload request
        $config = [
            'region'  => RotateConfig::region(),
            'version' => self::VERSION,
        ];

        // Set credentials
        $accessKeyID = RotateConfig::accesKeyID();
        $secretAccessKey = RotateConfig::secretAccessKey();
        $profile = RotateConfig::profile();
        if ($accessKeyID && $secretAccessKey) {
            // Explicitly provided credentials
            $config['credentials'] = new Credentials($accessKeyID, $secretAccessKey);
        } elseif ($profile) {
            // Else, use profile from ~/.aws/credentials
            $config['profile'] = $profile;
        }

        // Build client
        $client = new S3Client($config);
        return $client;
    }
}
