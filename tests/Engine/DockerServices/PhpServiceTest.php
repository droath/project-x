<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\PhpService;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestBase;

class PhpServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new PhpService(ProjectX::getEngineType());
        $this->classname = PhpService::class;
    }

    public function testName()
    {
        $this->assertEquals('php', call_user_func_array([$this->classname, 'name'], []));
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertInstanceOf(DockerService::class, $service);
        $this->assertEquals('./docker/services/php', $service->getBuild());
        $this->assertEquals(['9000'], $service->getExpose());
        $this->assertEquals([
            './:/var/www/html',
            './docker/services/php/www.conf:/usr/local/etc/php-fpm.d/www.conf',
            './docker/services/php/php-overrides.ini:/usr/local/etc/php/conf.d/99-php-overrides.ini'
        ], $service->getVolumes());
    }

    public function testDevService()
    {
        $dev_service = $this->service->devService();
        $this->assertInstanceOf(DockerService::class, $dev_service);
        $this->assertEquals(['.env'], $dev_service->getEnvFile());
        $this->assertEquals(['XDEBUG_CONFIG' => 'remote_host=${HOST_IP_ADDRESS}'], $dev_service->getEnvironment());
        $this->assertEquals(['docker-sync:/var/www/html:nocopy'], $dev_service->getVolumes());
    }

    public function testDevVolume()
    {
        $this->assertEquals([
            'docker-sync' => [
                'external' => [
                    'name' => '${SYNC_NAME}-docker-sync'
                ]
            ]
        ], $this->service->devVolumes());
    }

    public function testTemplateFiles()
    {
        $expected = [
            'Dockerfile' => [
                'variables' => [
                    'PHP_VERSION' => 7.1,
                    'PACKAGE_INSTALL' => 'file libpng12-dev libjpeg-dev libwebp-dev libpq-dev libmcrypt-dev libmagickwand-dev mysql-client nmap',
                    'PHP_PECL' => "&& pecl install redis \\\r\n  && pecl install xdebug \\\r\n  && pecl install imagick-3.4.3 \\\r\n  && pecl install Event_Dispatcher-1.1.0 \\",
                    'PHP_EXT_ENABLE' => 'redis xdebug imagick Event_Dispatcher',
                    'PHP_EXT_CONFIG' => 'gd --with-png-dir=/usr --with-jpeg-dir=/usr  --with-webp-dir=/usr',
                    'PHP_EXT_INSTALL' => 'gd zip pdo mcrypt opcache mbstring pdo_mysql soap',
                    'PHP_COMMANDS' => "RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \\\r\n  && mv /var/html/test.html /var/html/project.html"
                ],
                'overwrite' => true,
            ],
            'www.conf' => [],
            'php-overrides.ini' => []
        ];
        $configs = ProjectX::getProjectType()->serviceConfigs();
        $this->service->setConfigs($configs['php']);
        $this->assertEquals($expected, $this->service->templateFiles());
        // Update the PHP service to use a lower version.
        $this->service->setVersion('5.6');
        $expected['Dockerfile']['variables']['PHP_VERSION'] = '5.6';
        $expected['Dockerfile']['variables']['PHP_PECL'] = "&& pecl install redis \\\r\n  && pecl install xdebug-2.5.5 \\\r\n  && pecl install imagick-3.4.3 \\\r\n  && pecl install Event_Dispatcher-1.1.0 \\";
        // Verify that we're now get the property xdebug version.
        $this->assertEquals($expected, $this->service->templateFiles());
    }
}
