<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\Project\ProjectTypeFactory;
use Droath\ProjectX\Tests\TestBase;

/**
 * Define project type factory test.
 */
class ProjectTypeFactoryTest extends TestBase
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
        $this->factory = new ProjectTypeFactory(
            $this->container->get('projectXProjectResolver')
        );
    }

    public function testCreateInstance()
    {
        $this->assertInstanceOf('\Droath\ProjectX\Project\DrupalProjectType', $this->factory->createInstance());
    }
}
