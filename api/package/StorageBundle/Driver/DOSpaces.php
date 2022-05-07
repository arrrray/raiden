<?php

namespace Package\StorageBundle\Driver;

/**
 * @see https://github.com/utopia-php/storage
 */
class DOSpaces extends S3
{
    /**
     * Regions constants.
     */
    public const SGP1 = 'sgp1';
    public const NYC3 = 'nyc3';
    public const FRA1 = 'fra1';
    public const SFO2 = 'sfo2';
    public const SFO3 = 'sfo3';
    public const AMS3 = 'AMS3';

    /**
     * DOSpaces Constructor.
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::NYC3, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'.'.$region.'.digitaloceanspaces.com';
    }

    public function getName(): string
    {
        return 'Digitalocean Spaces Storage';
    }

    public function getDescription(): string
    {
        return 'Digitalocean Spaces Storage';
    }
}
