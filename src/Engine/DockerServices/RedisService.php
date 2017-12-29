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
    public function service()
    {
        return (new DockerService())
            ->setImage('redis', $this->getVersion())
            ->setPorts(['6379:6379'])
            ->setVolumes(['redis-data:/data']);
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
