<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Tests\TestBase;

class ProjectTypeResolverTest extends TestBase
{
    protected $projectTypeResolver;

    public function setUp()
    {
        parent::setUp();
        $this->projectTypeResolver = $this->container->get('projectXProjectResolver');
    }

    public function testTypes()
    {
        $types = $this->projectTypeResolver->types();
        $this->assertInternalType('array', $types);
        $this->assertArrayHasKey('drupal', $types);
    }

    public function testGetOptions()
    {
        $options = $this->projectTypeResolver->getOptions();
        $this->assertInternalType('array', $options);
        $this->assertEquals('Drupal', $options['drupal']);
    }

    public function testGetClassname()
    {
        $classname = $this->projectTypeResolver
            ->getClassname('drupal');

        $this->assertEquals($classname, 'Droath\ProjectX\Project\DrupalProjectType');
        $this->assertArrayHasKey('Droath\ProjectX\Project\ProjectTypeInterface', class_implements($classname));
    }

    public function testTypesWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $types = $this->projectTypeResolver->types();
        $this->assertInternalType('array', $types);
        $this->assertArrayHasKey('testing_project_type', $types);
    }

    public function testGetOptionsWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $options = $this->projectTypeResolver->getOptions();
        $this->assertInternalType('array', $options);
        $this->assertEquals('Testing Project Type', $options['testing_project_type']);
    }

    public function testGetClassnameWithPluginPackages()
    {
        $this->addComposerPluginStructure();
        $classname = $this->projectTypeResolver
            ->getClassname('testing_project_type');

        $this->assertEquals($classname, 'TestProjectType');
        $this->assertArrayHasKey('Droath\ProjectX\Project\ProjectTypeInterface', class_implements($classname));
    }
}
