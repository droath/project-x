<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\Config;

class ConfigTest extends TestBase
{
    protected $config;

    public function setUp()
    {
        parent::setUp();

        $this->config = new Config(
            $this->getProjectXFilePath()
        );
    }

    public function testGetName()
    {
        $this->assertEquals('Project-X Test', $this->config->getName());
    }

    public function testGetType()
    {
        $this->assertEquals('drupal', $this->config->getType());
    }

    public function testGetOption()
    {
        $this->assertEmpty($this->config->getOptions());
    }

    public function testGetConfig()
    {
        $config = $this->config->getConfig();
        $this->assertInternalType('array', $config);
        $this->assertEquals('drupal', $config['type']);
        $this->assertEquals('Project-X Test', $config['name']);
        $this->assertEquals('true', $config['host']['open_on_startup']);
    }

    public function testGetConfigOverrideLocal()
    {
        $this->addProjecXLocalConfigToRoot();

        $config = $this->config->getConfig();
        $this->assertEquals('drupal', $config['type']);
        $this->assertEquals('Project-X Local', $config['name']);
        $this->assertEquals('false', $config['host']['open_on_startup']);
    }

    public function testGetConfigLocal()
    {
        $this->assertEmpty($this->config->getConfigLocal());
        $this->addProjecXLocalConfigToRoot();
        $this->assertNotEmpty($this->config->getConfigLocal());
    }

    public function testHasConfig()
    {
        $this->assertTrue($this->config->hasConfig());
    }

    public function testHasConfigLocal()
    {
        $this->assertFalse($this->config->hasConfigLocal());
        $this->addProjecXLocalConfigToRoot();
        $this->assertTrue($this->config->hasConfigLocal());
    }
}
