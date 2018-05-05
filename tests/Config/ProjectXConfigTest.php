<?php

namespace Droath\ProjectX\Tests\Config;

use Droath\ProjectX\Config\ProjectXConfig;
use Droath\ProjectX\Tests\TestBase;

/**
 * Project-x config test.
 */
class ProjectXConfigTest extends TestBase
{
    protected $projectXConfig;

    public function setUp()
    {
        parent::setUp();

        $fileinfo = new \SplFileInfo($this->getProjectXFilePath());

        $this->projectXConfig = ProjectXConfig::createFromFile(
            $fileinfo
        );
    }

    public function testGetName()
    {
        $this->assertEquals('Project-X Test', $this->projectXConfig->getName());
    }

    public function testGetType()
    {
        $this->assertEquals('drupal', $this->projectXConfig->getType());
    }

    public function testGetEngine()
    {
        $this->assertEquals('docker', $this->projectXConfig->getEngine());
    }

    public function testGetNetwork()
    {
        $this->assertEquals(['proxy' => 'true'], $this->projectXConfig->getNetwork());
    }

    public function getGetGithub()
    {
        $github = $this->projectXConfig->getGithub();
        $this->assertEquals('https://github.com/droath/project-x', $github['url']);
    }

    public function testGetOption()
    {
        $this->assertNotEmpty($this->projectXConfig->getOptions());
    }
}
