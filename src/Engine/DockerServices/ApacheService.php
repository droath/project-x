<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerFrontendServiceTrait;
use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\ServiceInterface;

/**
 * Class ApacheService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class ApacheService extends DockerServiceBase implements ServiceInterface
{
    const DEFAULT_VERSION = 2.4;

    use DockerFrontendServiceTrait;

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'apache';
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
            ->setImage('httpd', $this->getVersion())
            ->setPorts(['80:80'])
            ->setVolumes([
               './:/var/www/html',
               './docker/services/apache/httpd.conf:/usr/local/apache2/conf/httpd.conf'
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function templateFiles()
    {
        $files = [
            'httpd.conf' => [],
        ];

        if ($php_service = $this->getLinkServiceNameByType('php')) {
            $files['httpd.conf']['overwrite'] = true;
            $files['httpd.conf']['variables']['PHP_SERVICE'] = $php_service;
        }

        return $files;
    }
}
