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
    public function ports()
    {
        return ['80'];
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        $service = (new DockerService())
           ->setImage('nginx', $this->getVersion())
           ->setVolumes([
               './:/var/www/html',
               './docker/nginx/nginx.conf:/etc/nginx/nginx.conf',
               './docker/nginx/default.conf:/etc/nginx/conf.d/default.conf'
           ]);

        return $this->alterService($service);
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
                    'PROJECT_ROOT' => ProjectX::getProjectType()->getInstallRoot(),
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
