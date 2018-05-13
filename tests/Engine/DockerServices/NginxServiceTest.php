<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\NginxService;
use Droath\ProjectX\Tests\TestBase;

class NginxServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new NginxService();
        $this->classname = NginxService::class;
    }

    public function testName()
    {
        $this->assertEquals('nginx', call_user_func_array([$this->classname, 'name'], []));
    }

    public function testGroup()
    {
        $this->assertEquals('frontend', call_user_func_array([$this->classname, 'group'], []));
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertInstanceOf(DockerService::class, $service);
        $this->assertEquals('nginx:stable', $service->getImage());
        $this->assertEquals(['80:80'], $service->getPorts());
        $this->assertEquals([
            './:/var/www/html',
            './docker/nginx/nginx.conf:/etc/nginx/nginx.conf',
            './docker/nginx/default.conf:/etc/nginx/conf.d/default.conf'
        ], $service->getVolumes());
    }

    public function testTemplateFiles()
    {
        $this->assertEquals([
            'nginx.conf' => [],
            'default.conf' => [
                'variables' => [
                    'HOSTNAME' => 'local.project-x-test.com',
                    'PHP_SERVICE' => 'php',
                    'PROJECT_ROOT' => '/www',
                ],
                'overwrite' => true,
            ]], $this->service->templateFiles());
    }
}
