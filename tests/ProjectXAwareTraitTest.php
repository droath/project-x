<?php

namespace Droath\ProjectX\Tests;

/*
 * Define Project-X aware trait test.
 */
class ProjectXAwareTraitTest extends TestBase
{
    public function setUp()
    {
        parent::setUp();

        $this->projectXAwareTrait = $this
            ->getMockForTrait('\Droath\ProjectX\ProjectXAwareTrait');

        $this->projectXAwareTrait
            ->setProjectXConfigPath($this->getProjectXFilePath());

    }

    public function testGetProjectXConfig()
    {
        $config = $this->projectXAwareTrait->getProjectXConfig();
        $this->assertEquals('Project-X Test', $config['name']);
        $this->assertEquals('true', $config['host']['open_on_startup']);

        $this->addProjecXLocalConfigToRoot();

        $config = $this->projectXAwareTrait->getProjectXConfig();
        $this->assertEquals('Project-X Local', $config['name']);
        $this->assertEquals('false', $config['host']['open_on_startup']);
    }

    public function testHasProjectXFile()
    {
        $this->assertTrue($this->projectXAwareTrait->hasProjectXFile());
        $this->projectXAwareTrait->setProjectXConfigPath('');
        $this->assertFalse($this->projectXAwareTrait->hasProjectXFile());
    }

    public function testHasProjectXLocalFile()
    {
        $this->assertFalse($this->projectXAwareTrait->hasProjectXLocalFile());
        $this->addProjecXLocalConfigToRoot();
        $this->assertTrue($this->projectXAwareTrait->hasProjectXLocalFile());
    }
}
