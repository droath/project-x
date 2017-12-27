<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\MysqlService;
use Droath\ProjectX\Tests\TestBase;

class MysqlServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new MysqlService();
        $this->classname = MysqlService::class;
    }

    public function testName()
    {
        $this->assertEquals('mysql', $this->classname::name());
    }

    public function testGroup()
    {
        $this->assertEquals('database', $this->classname::group());
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertInstanceOf(DockerService::class, $service);
        $this->assertEquals('mysql:5.6', $service->getImage());
        $this->assertEquals(['3306:3306'], $service->getPorts());
        $this->assertEquals([
            'mysql-data:/var/lib/mysql',
            './docker/services/mysql/mysql-overrides.cnf:/etc/mysql/mysql.conf.d/99-mysql-overrides.cnf'
        ], $service->getVolumes());
    }

    public function testVolumes()
    {
        $this->assertEquals([
            'mysql-data' => [
                'driver' => 'local'
            ]
        ], $this->service->volumes());
    }

    public function testTemplateFiles()
    {
        $this->assertArrayHasKey('mysql-overrides.cnf', $this->service->templateFiles());
    }
}
