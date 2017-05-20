<?php

namespace Droath\ProjectX\Tests\Template;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Template\TemplateManager;
use Droath\ProjectX\Tests\TestBase;
use org\bovigo\vfs\vfsStream;

/**
 * Define template manager tests.
 */
class TemplateManagerTest extends TestBase
{
    protected $templateManager;

    public function setUp()
    {
        parent::setUp();

        $this->templateManager = (new TemplateManager())->setSearchDirectories(ProjectX::getProjectType()->templateDirectories());
    }

    public function testLoadTemplate()
    {
        $composer_json = json_encode([
            'require' => [
                'drupal/core' => '^8.1'
            ]
        ]);

        vfsStream::create([
            'project-x' => [
                'composer.json' => $composer_json
            ]
        ], $this->projectDir);

        $contents = $this->templateManager
            ->loadTemplate('composer.json', 'json');

        $this->assertArrayHasKey('drupal/core', $contents['require']);
    }

    public function testTemplateFilePathTempDir()
    {
        $filename = '.travis.yml';

        $path = $this
            ->templateManager
            ->getTemplateFilePath($filename);

        $expected = APP_ROOT .  "/templates/drupal/$filename";

        $this->assertEquals($expected, $path);
    }

    public function testTemplateFilePathFromProjectDir()
    {
        $filename = '.travis.yml';

        $this
            ->createProjectFile($filename);

        $path = $this
            ->templateManager
            ->getTemplateFilePath($filename);

        $expected = TemplateManager::PROJECT_DIRECTORY . "/$filename";

        $this->assertEquals(vfsStream::url("root$expected"), $path);
    }

    public function testTemplateFilePathNonExistent()
    {
        $path = $this->templateManager
            ->getTemplateFilePath('nothing.yml');

        $this->assertFalse($path);
    }

    public function testHasProjectTemplateDirectoryDoesNotExist()
    {
        $this->assertFalse(
            $this->templateManager->hasProjectTemplateDirectory()
        );
    }

    public function testHasProjectTemplateDirectoryDoesExist()
    {
        $this->createProjectDir();

        $this->assertTrue(
            $this->templateManager->hasProjectTemplateDirectory()
        );
    }

    /**
     * Create project directory.
     *
     * @return \org\bovigo\vfs\vfsStreamDirectory
     */
    protected function createProjectDir()
    {
        return vfsStream::newDirectory(TemplateManager::PROJECT_DIRECTORY)
            ->at($this->projectDir);
    }

    /**
     * Create file in project directory.
     *
     * @param string $name
     *   The project file name.
     * @param string $contents
     *   The project file contents.
     *
     * @return \org\bovigo\vfs\vfsStreamFile
     */
    protected function createProjectFile($name, $contents = '')
    {
        return vfsStream::newFile($name)
            ->withContent($contents)
            ->at($this->createProjectDir());
    }
}
