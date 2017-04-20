<?php

namespace Droath\ProjectX\Tests\Config;

use Droath\ProjectX\Config\ComposerConfig;
use Droath\ProjectX\Tests\TestBase;

class ComposerConfigTest extends TestBase
{
    protected $composerConfig;

    public function setUp()
    {
        parent::setUp();
        $this->composerConfig = new ComposerConfig();
    }

    public function testCreateFromArray()
    {
        $data = [
            'name' => 'New Project',
            'description' => 'My composer description.',
            'require' => [
                'droath/project-x' => '^0.2',
            ],
        ];
        $object = ComposerConfig::createFromArray($data);

        $this->assertInstanceOf('\Droath\ProjectX\Config\ComposerConfig', $object);
    }

    public function testCreateFromFile()
    {
        $object = $this->getInstanceFromFile();

        $this->assertInstanceOf('\Droath\ProjectX\Config\ComposerConfig', $object);
    }

    public function testUpdate()
    {
        $object = $this->getInstanceFromFile();
        $updated_object = $object->update(['name' => 'Testing Project']);

        $this->assertEquals('Testing Project', $updated_object->getName());
    }

    public function testSave()
    {
        $object = $this->getInstanceFromFile();
        $filename = $this->getProjectFileUrl('composer.json');

        $object->save($filename);

        $this->assertProjectFileExists('composer.json');
        $this->assertJsonFileEqualsJsonFile(APP_ROOT . '/tests/fixtures/composer-test.json', $filename);
    }

    public function testToFormat()
    {
        $object = $this->getInstanceFromFile();
        $format = $object->toFormat();

        $this->assertJsonStringEqualsJsonFile(APP_ROOT . '/tests/fixtures/composer-test.json', $format);
    }

    public function testToArray()
    {
        $object = $this->getInstanceFromFile();

        $this->assertInternalType('array', $object->toArray());
    }

    protected function getInstanceFromFile()
    {
        $filename = new \SplFileInfo(APP_ROOT . '/tests/fixtures/composer-test.json');

        return ComposerConfig::createFromFile($filename);
    }
}
