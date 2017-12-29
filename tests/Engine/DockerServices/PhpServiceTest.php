<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\PhpService;
use Droath\ProjectX\Tests\TestBase;

class PhpServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new PhpService();
        $this->classname = PhpService::class;
    }

    public function testName()
    {
        $this->assertEquals('php', $this->classname::name());
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertInstanceOf(DockerService::class, $service);
        $this->assertEquals('./docker/services/php', $service->getBuild());
        $this->assertEquals(['9000'], $service->getExpose());
        $this->assertEquals([
            './:/var/www/html',
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
        $this->assertEquals([
            'DockerFile' => [
                'variables' => [
                    'DOCKER_PHP_VERSION' => 7.1,
                ],
                'overwrite' => true,
            ],
            'php-overrides.ini' => []
        ], $this->service->templateFiles());
    }
}
