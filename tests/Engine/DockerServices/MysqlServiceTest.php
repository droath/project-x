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
        $this->assertEquals('mysql', call_user_func_array([$this->classname, 'name'], []));
    }

    public function testGroup()
    {
        $this->assertEquals('database', call_user_func_array([$this->classname, 'group'], []));
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertInstanceOf(DockerService::class, $service);
        $this->assertEquals([
            'image' => 'mysql:5.6',
            'ports' => ['3306:3306'],
            'volumes' => [
                'mysql-data:/var/lib/mysql',
                './docker/services/mysql/mysql-overrides.cnf:/etc/mysql/mysql.conf.d/99-mysql-overrides.cnf'
            ],
            'environment' => [
                'MYSQL_USER=admin',
                'MYSQL_PASSWORD=root',
                "MYSQL_DATABASE=drupal",
                'MYSQL_ALLOW_EMPTY_PASSWORD=1'
            ]
        ], $service->asArray());
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

    public function testUsername()
    {
        $this->assertEquals('admin', $this->service->username());
    }

    public function testPassword()
    {
        $this->assertEquals('root', $this->service->password());
    }

    public function testDatabase()
    {
        $this->assertEquals('drupal', $this->service->database());
    }

    public function testProtocol()
    {
        $this->assertEquals('mysql', $this->service->protocol());
    }
}
