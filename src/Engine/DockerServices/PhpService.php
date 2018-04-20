<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\ServiceInterface;

/**
 * Class PhpService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class PhpService extends DockerServiceBase implements ServiceInterface
{
    const DEFAULT_VERSION = 7.1;

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'php';
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        return (new DockerService())
            ->setBuild('./docker/services/php')
            ->setExpose(['9000'])
            ->setVolumes([
                './:/var/www/html',
                './docker/services/php/www.conf:/usr/local/etc/php-fpm.d/www.conf',
                './docker/services/php/php-overrides.ini:/usr/local/etc/php/conf.d/99-php-overrides.ini'
            ]);
    }

    public function devService()
    {
        return (new DockerService())
            ->setEnvFile([
                '.env'
            ])
            ->setEnvironment([
                'XDEBUG_CONFIG' => 'remote_host=${HOST_IP_ADDRESS}'
            ])
            ->setVolumes([
                'docker-sync:/var/www/html:nocopy'
            ]);
    }

    public function devVolumes()
    {
        return [
            'docker-sync' => [
                'external' => [
                    'name' => '${SYNC_NAME}-docker-sync'
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function templateFiles()
    {
        return [
            'Dockerfile' => [
                'variables' => [
                    'DOCKER_PHP_VERSION' => $this->getVersion(),
                ],
                'overwrite' => true,
            ],
            'www.conf' => [],
            'php-overrides.ini' => []
        ];
    }
}
