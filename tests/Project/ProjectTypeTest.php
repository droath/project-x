<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Project\ProjectType;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestTaskBase;
use org\bovigo\vfs\vfsStream;

/**
 * Define project type test class.
 */
class ProjectTypeTest extends TestTaskBase
{

    protected $projectType;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->projectType = $this
            ->getMockForAbstractClass('\Droath\ProjectX\Project\ProjectType');

        $this->projectType
            ->setBuilder($this->builder)
            ->setContainer($this->container);
    }

    public function testIsBuild()
    {
        $this->assertFalse($this->projectType->isBuilt());

        $directory = vfsStream::create([
            'www' => [
                'sites' => [
                    'default' => [
                        'files' => [
                            'README.md',
                            'images' => [],
                        ],
                    ],
                ],
            ],
        ], $this->projectDir);

        $this->assertTrue($this->projectType->isBuilt());
    }

    public function testInstallRoot()
    {
        $this->assertEquals('/www', ProjectType::installRoot());

        ProjectX::getProjectConfig()
            ->setRoot('docroot')
            ->save($this->getProjectXFilePath());

        $this->assertEquals('/docroot', ProjectType::installRoot());
    }

    public function testHasDockerSupport()
    {
        $this->assertFalse($this->projectType->hasDockerSupport());
        $this->projectType->supportsDocker();
        $this->assertTrue($this->projectType->hasDockerSupport());
    }

    public function testSetupProjectFilesystem()
    {
        chmod($this->projectRoot, 0644);
        $this->assertFilePermission('0644', $this->projectRoot);
        $this->projectType->setupProjectFilesystem();
        $this->assertFilePermission('0775', $this->projectRoot);
        $this->assertProjectFilePermission('0775', 'www');
    }

    public function testGetProjectOptionByKey()
    {
        $site = $this->projectType->getProjectOptionByKey('site');
        $this->assertEquals('Drupal-X Site', $site['name']);
        $this->assertEquals('standard', $site['profile']);
        $this->assertFalse($this->projectType->getProjectOptionByKey('nothing'));
    }
}
