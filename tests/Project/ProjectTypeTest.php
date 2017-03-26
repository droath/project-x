<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Tests\TestTaskBase;
use org\bovigo\vfs\vfsStream;

/**
 * Define project type test class.
 */
class ProjectTypeTest extends TestTaskBase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->projectType = $this->getMockForAbstractClass('\Droath\ProjectX\Project\ProjectType');

        $this->projectType
            ->setBuilder($this->builder)
            ->setContainer($this->container)
            ->setProjectXConfigPath($this->getProjectXFilePath());
    }

    public function testIsBuild()
    {
        $this->assertFalse($this->projectType->isBuilt());

        $directory = vfsStream::create([
            'docroot' => [
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
        $this->assertProjectFilePermission('0775', 'docroot');
    }

}
