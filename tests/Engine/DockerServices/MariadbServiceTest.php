<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\MariadbService;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestBase;

class MariadbServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new MariadbService(ProjectX::getEngineType());
        $this->classname = MariadbService::class;
    }

    public function testName()
    {
        $this->assertEquals('mariadb', call_user_func_array([$this->classname, 'name'], []));
    }

    public function testGroup()
    {
        $this->assertEquals('database', call_user_func_array([$this->classname, 'group'], []));
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertInstanceOf(DockerService::class, $service);
        $this->assertEquals('mariadb:5.5', $service->getImage());
        $this->assertEquals(['3306:3306'], $service->getPorts());
        $this->assertEquals([
            'mysql-data:/var/lib/mysql',
            './docker/services/mariadb/mysql-overrides.cnf:/etc/mysql/mysql.conf.d/99-mysql-overrides.cnf'
        ], $service->getVolumes());
    }

    public function testTemplateFiles()
    {
        $this->assertArrayHasKey('mysql-overrides.cnf', $this->service->templateFiles());
    }
}
