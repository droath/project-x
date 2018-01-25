<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\RedisService;
use Droath\ProjectX\Tests\TestBase;

class RedisServiceTest extends TestBase
{
    protected $service;
    protected $classname;

    public function setUp() {
        parent::setUp();
        $this->service = new RedisService();
        $this->classname = RedisService::class;
    }

    public function testName()
    {
        $this->assertEquals('redis', call_user_func_array([$this->classname, 'name'], []));
    }

    public function testService()
    {
        $service = $this->service->service();
        $this->assertInstanceOf(DockerService::class, $service);
        $this->assertEquals('redis:4.0', $service->getImage());
        $this->assertEquals(['6379:6379'], $service->getPorts());
        $this->assertEquals([
            'redis-data:/data',
        ], $service->getVolumes());
    }

    public function testVolumes()
    {
        $this->assertEquals([
            'redis-data' => [
                'driver' => 'local'
            ]
        ], $this->service->volumes());
    }
}
