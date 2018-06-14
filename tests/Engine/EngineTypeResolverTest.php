<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Tests\TestBase;

class EngineTypeResolverTest extends TestBase
{
    protected $engineTypeResolver;

    public function setUp()
    {
        parent::setUp();
        $this->engineTypeResolver = $this->container->get('projectXEngineResolver');
    }

    public function testTypes()
    {
        $types = $this->engineTypeResolver->types();
        $this->assertInternalType('array', $types);
        $this->assertArrayHasKey('docker', $types);
    }

    public function testGetOptions()
    {
        $options = $this->engineTypeResolver->getOptions();
        $this->assertInternalType('array', $options);
        $this->assertEquals('Docker', $options['docker']);
    }

    public function testGetClassname()
    {
        $classname = $this->engineTypeResolver
            ->getClassname('docker');

        $this->assertEquals($classname, 'Droath\ProjectX\Engine\DockerEngineType');
        $this->assertArrayHasKey('Droath\ProjectX\Engine\EngineTypeInterface', class_implements($classname));
    }

    public function testTypesWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $types = $this->engineTypeResolver->types();
        $this->assertInternalType('array', $types);
        $this->assertArrayHasKey('testing_engine_type', $types);
    }

    public function testGetOptionsWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $options = $this->engineTypeResolver->getOptions();
        $this->assertInternalType('array', $options);
        $this->assertEquals('Testing Engine Type', $options['testing_engine_type']);
    }

    public function testGetClassnameWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $classname = $this->engineTypeResolver
            ->getClassname('testing_engine_type');

        $this->assertEquals($classname, 'TestEngineType');
        $this->assertArrayHasKey('Droath\ProjectX\Engine\EngineTypeInterface', class_implements($classname));
    }
}
