<?php

namespace Droath\ProjectX\Tests\Engine;

use Droath\ProjectX\Engine\EngineType;
use Droath\ProjectX\Engine\ServiceInterface;
use Droath\ProjectX\Tests\TestBase;

/**
 * Class EngineTypeTest
 *
 * @package Droath\ProjectX\Tests\Engine
 */
class EngineTypeTest extends TestBase
{
    protected $engineType;

    public function setUp() {
        parent::setUp();
        $this->engineType = $this->getMockForAbstractClass(
            EngineType::class,
            [],
            '',
            true,
            true,
            true,
            []
        );
    }

    public function testGetServices()
    {
        $services = $this->engineType->getServices();

        $this->assertNotEmpty($services);
        $this->assertArrayHasKey('web', $services);
        $this->assertArrayHasKey('web2', $services);
        $this->assertArrayHasKey('php', $services);
        $this->assertArrayHasKey('database', $services);
        $this->assertArrayHasKey('database2', $services);
    }

    public function testGetServiceNamesByType()
    {
        $names = $this->engineType->getServiceNamesByType('apache');
        $this->assertEquals('web', $names[0]);
    }

    public function testServices()
    {
        EngineType::setServices([
            'mock' => $this->createMock(ServiceInterface::class),
        ]);
        $this->assertCount(1, EngineType::services());
        $this->assertInternalType('array', EngineType::services());
    }

    public function testHasService()
    {
        $this->assertTrue($this->engineType->hasServices());
    }

    public function testGetInstallPath()
    {
        $this->assertEquals('vfs://root', $this->engineType->getInstallPath());
    }
}
