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
                    'DOCKER_PHP_VERSION' => 7.1,
                    'PHP_XDEBUG_VERSION' => null,
                ],
                'overwrite' => true,
            ],
            'www.conf' => [],
            'php-overrides.ini' => []
        ];
        $this->assertEquals($expected, $this->service->templateFiles());
        // Update the PHP service to use a lower version.
        $this->service->setVersion('5.6');
        $expected['Dockerfile']['variables']['DOCKER_PHP_VERSION'] = '5.6';
        $expected['Dockerfile']['variables']['PHP_XDEBUG_VERSION'] = '-2.5.5';
        // Verify that we're now get the property xdebug version.
        $this->assertEquals($expected, $this->service->templateFiles());
    }
}
