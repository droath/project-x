<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\PostgresService;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestBase;

class PostgresServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new PostgresService(ProjectX::getEngineType());
        $this->classname = PostgresService::class;
    }

    public function testName()
    {
        $this->assertEquals('postgres', call_user_func_array([$this->classname, 'name'], []));
    }

    public function testGroup()
    {
        $this->assertEquals('database', call_user_func_array([$this->classname, 'group'], []));
    }

    public function testPorts()
    {
        $this->assertEquals(['5432'], $this->service->ports());
    }

    public function testService() {
        $service = $this->service->service();
        $this->assertEquals([
            'image'       => 'postgres:9.6',
            'ports'       => ['5432:5432'],
            'volumes'     => [
                'pgsql-data:/var/lib/postgresql/data'
            ],
            'environment' => [
                'POSTGRES_USER=admin',
                'POSTGRES_PASSWORD=root',
                "POSTGRES_DB=drupal",
                'PGDATA=/var/lib/postgresql/data'
            ]
        ], $service->asArray());
    }

    public function testVolumes()
    {
        $this->assertEquals([
            'pgsql-data' => [
                'driver' => 'local'
            ]
        ], $this->service->volumes());
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
        $this->assertEquals('pgsql', $this->service->protocol());
    }
}
