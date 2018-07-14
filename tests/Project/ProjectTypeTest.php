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

    public function testGetInstallPath()
    {
        $this->assertEquals('vfs://root/www', $this->projectType->getInstallPath());
    }

    public function testGetProjectVersion()
    {
        $this->addProjectXConfigToRoot();
        $this->assertEquals('8', $this->projectType->getProjectVersion());
    }

    public function testGetInstallRoot()
    {
        $this->assertEquals('/www', $this->projectType->getInstallRoot());
        $this->assertEquals('www', $this->projectType->getInstallRoot(true));
    }

    public function testGetQualifiedInstallRoot()
    {
        $this->assertEquals('vfs://root/www', $this->projectType->getQualifiedInstallRoot());
    }

    public function testOnDeployBuild()
    {
        $directory = vfsStream::create([
            'build' => [],
        ], $this->projectDir);

        $this->projectType->onDeployBuild('vfs://root/build');
        $this->assertTrue($directory->getChild('build')->hasChild('www'));
    }
}
