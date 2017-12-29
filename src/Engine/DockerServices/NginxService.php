<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\ServiceInterface;
use Droath\ProjectX\ProjectX;
use Droath\RoboDockerCompose\DockerServicesTrait;

/**
 * Class NginxService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class NginxService extends DockerServiceBase implements ServiceInterface
{
    const DEFAULT_VERSION = 'stable';

    use DockerServicesTrait;

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'nginx';
    }

    /**
     * {@inheritdoc}
     */
    public static function group()
    {
        return 'frontend';
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        return (new DockerService())
           ->setImage('nginx', $this->getVersion())
           ->setPorts(['80:80'])
           ->setVolumes([
               './:/var/www/html',
               './docker/nginx/nginx.conf:/etc/nginx/nginx.conf',
               './docker/nginx/default.conf:/etc/nginx/conf.d/default.conf'
           ]);
    }

    /**
     * {@inheritdoc}
     */
    public function templateFiles()
    {
        $host = ProjectX::getProjectConfig()->getHost();
        $files = [
            'nginx.conf' => [],
            'default.conf' => [
                'variables' => [
                    'HOSTNAME' => isset($host['name'])
                        ? $host['name']
                        : 'project-x.local',
                ],
                'overwrite' => true,
            ],
        ];

        if ($php_service = $this->getLinkServiceNameByType('php')) {
            $files['default.conf']['variables']['PHP_SERVICE'] = $php_service;
        }

        return $files;
    }
}
