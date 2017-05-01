<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\Engine\EngineTypeFactory;
use Droath\ProjectX\Tests\TestBase;

/**
 * Define engine type factory test.
 */
class EngineTypeFactoryTest extends TestBase
{
    /**
     * Factory.
     *
     * @var \Droath\ProjectX\FactoryInterface
     */
    protected $factory;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new EngineTypeFactory(
            $this->container->get('projectXEngineResolver')
        );
    }

    public function testCreateInstance()
    {
        $this->assertInstanceOf('\Droath\ProjectX\Engine\DockerEngineType', $this->factory->createInstance());
    }
}
