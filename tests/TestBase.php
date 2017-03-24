<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\Filesystem\YamlFilesystem;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Define Project-X test base class.
 */
abstract class TestBase extends TestCase
{
    /**
     * Project-X directory.
     *
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $projectDir;

    /**
     * Project-X root URL.
     *
     * @var string
     */
    protected $projectRoot;

    /**
     * Project-X file name.
     *
     * @var string
     */
    protected $projectFileName = 'project-x.yml';

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', '.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->projectDir = vfsStream::setup('root');
        $this->projectRoot = vfsStream::url('root');

        (new YamlFilesystem(
            $this->getProjectXFileContents(),
            $this->projectRoot
        ))
        ->save($this->projectFileName);
    }

    protected function getProjectXFileContents()
    {
        return [
            'name' => 'Project-X Test',
            'type' => 'drupal',
            'engine' => 'docker',
            'host' => [
                'name' => 'local.project-x-test.com',
                'open_on_startup' => 'true',
            ],
        ];
    }

    protected function getProjectFileUrl($filename)
    {
        return vfsStream::url("root/{$filename}");
    }
}
