<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\ProjectX;

/**
 * Define Project-X tests.
 */
class ProjectXTest extends TestBase
{
    protected $projectX;

    public function setUp()
    {
        parent::setUp();

        $path = $this->getProjectFileUrl($this->projectFileName);
        $this->projectX = (new ProjectX())
            ->setProjectXConfigPath($path);
    }

    public function testHasProjectXConfig()
    {
        $this->assertTrue($this->projectX->hasProjectXConfig());
    }

    public function testProjectMachineName()
    {
        $this->assertEquals('project-x_test', $this->projectX->getProjectMachineName());
    }
}
