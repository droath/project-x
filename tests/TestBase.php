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
     * Project-X local file name.
     *
     * @var string
     */
    protected $projectLocalFileName = 'project-x.local.yml';

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

        $this->addProjectXConfigToRoot();
    }

    /**
     * Add Project-X config to project root.
     */
    protected function addProjectXConfigToRoot()
    {
        (new YamlFilesystem(
            $this->getProjectXFileContents(),
            $this->projectRoot
        ))
        ->save($this->projectFileName);

        return $this;
    }

    /**
     * Add Project-X local config to project root.
     */
    protected function addProjecXLocalConfigToRoot()
    {
        (new YamlFilesystem(
            $this->getprojectXLocalFileContents(),
            $this->projectRoot
        ))
        ->save($this->projectLocalFileName);

        return $this;
    }

    /**
     * Get Project-X file contents fixture.
     *
     * @return array
     */
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

    /**
     * The project-X local file contents fixture.
     *
     * @return array
     */
    protected function getprojectXLocalFileContents()
    {
        return [
            'name' => 'Project-X Local',
            'type' => 'drupal',
            'host' => [
                'open_on_startup' => 'false',
            ],
        ];
    }

    /**
     * Get Project-X file path.
     *
     * @return string
     */
    protected function getProjectXFilePath()
    {
        return $this->getProjectFileUrl($this->projectFileName);
    }

    /**
     * Get project file URL.
     *
     * @param string $filename
     *   The project filename.
     *
     * @return string
     */
    protected function getProjectFileUrl($filename)
    {
        return vfsStream::url("root/{$filename}");
    }

    /**
     * Assert if the project file exists.
     *
     * @param string $path
     *   Path to file.
     */
    protected function assertProjectFileExists($path)
    {
        $filename = $this->getProjectFileUrl($path);
        $this->assertFileExists($filename);
    }

    /**
     * Assert if project file permission match.
     *
     * @param string $expected
     *   The expected file permissions.
     * @param string $path
     *   Path to file.
     */
    protected function assertProjectFilePermission($expected, $path)
    {
        $filename = $this->getProjectFileUrl($path);
        $this->assertFilePermission($expected, $filename);
    }

    /**
     * Assert if file permission match.
     *
     * @param string $expected
     *   The expected file permissions.
     * @param string $filename
     *   The valid filename.
     * @param string $message
     *   A message to display if failed.
     */
    protected function assertFilePermission($expected, $filename, $message = null)
    {
        $this->assertFileExists($filename, $message);
        $this->assertEquals($expected, substr(sprintf('%o', fileperms($filename)), -4), $message);
    }
}
