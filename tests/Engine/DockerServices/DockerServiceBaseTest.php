<?php

namespace Droath\ProjectX\Tests\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\DockerServices\DockerServiceBase;
use Droath\ProjectX\Engine\DockerServices\MysqlService;
use Droath\ProjectX\Tests\TestBase;

class DockerServiceBaseTest extends TestBase
{
    protected $service;

    public function setUp() {
        parent::setUp();
        $this->service = $this->getMockForAbstractClass(
            DockerServiceBase::class,
            [],
            '',
            true,
            true,
            true,
            ['name', 'service']
        );
    }

    public function testSetVersion()
    {
        $this->service->setVersion('4.4');
        $this->assertEquals('4.4', $this->service->getVersion());
    }

    public function testGetVersion()
    {
        $this->assertEquals('latest', $this->service->getVersion());
        $this->service->setVersion('3.3');
        $this->assertEquals('3.3', $this->service->getVersion());
    }

    public function testVolumes()
    {
        $this->assertInternalType('array', $this->service->volumes());
    }

    public function testDevVolumes()
    {
        $this->assertInternalType('array', $this->service->devVolumes());
    }

    public function testDevService()
    {
        $this->assertInstanceOf(DockerService::class, $this->service->devService());
    }

    public function testTemplateFiles()
    {
        $this->assertInternalType('array', $this->service->templateFiles());
    }

    public function testGetService()
    {
        $this->service
            ->expects($this->any())
            ->method('name')
            ->will($this->returnValue('mysql'));

        $this->service
            ->expects($this->any())
            ->method('service')
            ->will($this->returnValue((new MysqlService())->service()));

        $service = $this->service->getService();
        $this->assertEquals(['3307:3307'], $service->getPorts());
        $this->assertEquals([
            'MYSQL_USER=admin',
            'MYSQL_PASSWORD=root',
            'MYSQL_DATABASE=drupal',
            'MYSQL_ALLOW_EMPTY_PASSWORD=1'
        ], $service->getEnvironment());
        $this->assertEquals(['web1'], $service->getLinks());
    }

    public function testGetEnvironmentValue()
    {
        $this->service
            ->expects($this->any())
            ->method('name')
            ->will($this->returnValue('mysql'));

        $this->service
            ->expects($this->any())
            ->method('service')
            ->will($this->returnValue((new MysqlService())->service()));

        $this->assertEquals('admin', $this->service->getEnvironmentValue('MYSQL_USER'));
        $this->assertEquals('1', $this->service->getEnvironmentValue('MYSQL_ALLOW_EMPTY_PASSWORD'));
    }

    public function testGetHostPosts()
    {
        $this->service
            ->expects($this->any())
            ->method('service')
            ->will($this->returnValue((new MysqlService())->service()));

        $this->service
            ->expects($this->any())
            ->method('name')
            ->will($this->returnValue('mysql'));

        $ports = $this->service->getHostPorts();
        $this->assertEquals('3307', current($ports));
    }
}
