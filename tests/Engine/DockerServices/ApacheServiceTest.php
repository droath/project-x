<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerServices\ApacheService;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestBase;

class ApacheServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new ApacheService(ProjectX::getEngineType());
        $this->classname = ApacheService::class;
    }

    public function testName()
    {
        $this->assertEquals('apache', call_user_func_array([$this->classname, 'name'], []));
    }

    public function testGroup()
    {
        $this->assertEquals('frontend', call_user_func_array([$this->classname, 'group'], []));
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertEquals([
            'image'       => 'httpd:2.4',
            'ports'       => ['80:80'],
            'volumes'     => [
                './:/var/www/html',
                './docker/services/apache/httpd.conf:/usr/local/apache2/conf/httpd.conf',
                './docker/services/apache/httpd-mpm.conf:/usr/local/apache2/conf/extra/httpd-mpm.conf'
            ],
        ], $service->asArray());
    }

    public function testTemplateFiles()
    {
        $this->assertEquals([
            'httpd-mpm.conf' => [],
            'httpd.conf' => [
                'variables' => [
                    'PHP_SERVICE' => 'php',
                    'PROJECT_ROOT' => '/www'
                ],
                'overwrite' => true,
            ]], $this->service->templateFiles());
    }
}
