<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\ServiceInterface;

/**
 * Class RedisService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class RedisService extends DockerServiceBase implements ServiceInterface
{
    const DEFAULT_VERSION = '4.0';

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'redis';
    }

    /**
     * {@inheritdoc}
     */
    public function ports()
    {
        return ['6379'];
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        $service = (new DockerService())
            ->setImage('redis', $this->getVersion())
            ->setVolumes(['redis-data:/data']);

        return $this->alterService($service);
    }

    /**
     * {@inheritdoc}
     */
    public function volumes()
    {
        return [
            'redis-data' => [
                'driver' => 'local'
            ]
        ];
    }
}
