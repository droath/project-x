<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Platform\NullPlatformType;
use Droath\ProjectX\Tests\TestBase;

class PlatformTypeResolverTest extends TestBase
{
    protected $platformTypeResolver;

    public function setUp()
    {
        parent::setUp();
        $this->platformTypeResolver = $this->container->get('projectXPlatformResolver');
    }

    public function testTypes()
    {
        $types = $this->platformTypeResolver->types();
        $this->assertEmpty($types);
    }

    public function testGetOptions()
    {
        $options = $this->platformTypeResolver->getOptions();
        $this->assertEmpty($options);
    }

    public function testGetClassname()
    {
        $classname = $this->platformTypeResolver
            ->getClassname('null');
        $this->assertEquals(NullPlatformType::class, $classname);
    }

    public function testTypesWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $types = $this->platformTypeResolver->types();
        $this->assertInternalType('array', $types);
        $this->assertArrayHasKey('testing_platform_type', $types);
    }

    public function testGetOptionsWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $options = $this->platformTypeResolver->getOptions();
        $this->assertInternalType('array', $options);
        $this->assertEquals('Testing Platform Type', $options['testing_platform_type']);
    }

    public function testGetClassnameWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $classname = $this->platformTypeResolver
            ->getClassname('testing_platform_type');
        $this->assertEquals($classname, 'TestPlatformType');
        $this->assertArrayHasKey('Droath\ProjectX\Platform\PlatformTypeInterface', class_implements($classname));
    }
}
