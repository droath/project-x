<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Project\DrupalProjectType;
use Droath\ProjectX\Tests\TestTaskBase;
use org\bovigo\vfs\vfsStream;

/**
 * Define Drupal project type test.
 */
class DrupalProjectTypeTest extends TestTaskBase
{
    /**
     * Drupal project type.
     *
     * @var \Droath\ProjectX\Project\DrupalProjectType
     */
    protected $drupalProject;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->drupalProject = (new DrupalProjectType())
            ->setBuilder($this->builder)
            ->setContainer($this->container);
    }

    public function testOnEngineUp()
    {
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

        $files_url = $this->getProjectFileUrl('docroot/sites/default/files');
        chmod($files_url, 0664);
        $this->assertFilePermission('0664', $files_url);
        $this->drupalProject->onEngineUp();
        $this->assertFilePermission('0775', $files_url);
        $this->assertFilePermission('0775', "{$files_url}/images", 'chmod should be done recursively.');
    }

    public function testSetupProject()
    {
        $this->drupalProject->setupProject();
        $this->assertTrue($this->projectDir->hasChild('.gitignore'));
    }

    public function testSetupDrush()
    {
        $this->drupalProject->setupDrush();
        $this->assertProjectFileExists('drush.wrapper');
        $this->assertTrue($this->projectDir->hasChild('drush'));
    }

    public function testSetupDrushAlias()
    {
        vfsStream::copyFromFileSystem(
            __DIR__ . '/../../templates/drupal/drush',
            vfsStream::newDirectory('drush')
                ->at($this->projectDir)
        );
        $this->drupalProject->setupDrushAlias();

        $contents = file_get_contents(
            $this->getProjectFileUrl('drush/site-aliases/local.aliases.drushrc.php')
        );

        $this->assertProjectFileExists('drush/site-aliases/local.aliases.drushrc.php');
        $this->assertRegExp('/\$aliases\[\'project-x-test\'\]/', $contents);
        $this->assertRegExp('/\'uri\'\s?=>\s?\'local\.project-x-test\.com\',/', $contents);
        $this->assertRegExp('/\'root\'\s?=>\s?\'vfs:\/\/root\/docroot\'/', $contents);
    }

    public function testSetupDrupalFilesystem()
    {
        $directory = vfsStream::create([
            'docroot' => [
                'sites' => [
                    'default' => [],
                ],
            ],
        ], $this->projectDir);

        $this->drupalProject->setupDrupalFilesystem();

        $this->assertProjectFilePermission('0775', 'docroot/sites');
        $this->assertProjectFileExists('docroot/sites/default/files');
    }

    public function testSetupDrupalSettings()
    {
        $settings_content = file_get_contents(APP_ROOT . '/tests/fixtures/default.settings.php');

         vfsStream::create([
            'docroot' => [
                'sites' => [
                    'default' => [
                        'settings.php' => $settings_content,
                    ],
                ],
            ],
        ], $this->projectDir);

        $this->drupalProject->setupDrupalSettings();

        $settings_url = $this->getProjectFileUrl('docroot/sites/default/settings.php');
        $settings_content = file_get_contents($settings_url);

        $this->assertProjectFileExists('salt.txt');
        $this->assertProjectFileExists('docroot/sites/default/settings.php');
        $this->assertGreaterThan(10, filesize($this->getProjectFileUrl('salt.txt')));
        $this->assertRegExp('/include.+\/settings\.local\.php\"\;/', $settings_content);
        $this->assertRegExp('/\$settings\[\'hash_salt\'].+\/salt\.txt\'\)\;/', $settings_content);
    }

    public function testSetupDrupalLocalSettings()
    {
        $directory = vfsStream::create([
            'docroot' => [
                'sites' => [
                    'example.settings.local.php' => "<?php print 'local settings';\n\n",
                ],
            ],
        ], $this->projectDir);

        $this->drupalProject->setupDrupalLocalSettings();

        $settings_local_url = $this->getProjectFileUrl('docroot/sites/default/settings.local.php');

        $this->assertProjectFileExists('docroot/sites/default/settings.local.php');
        $this->assertRegExp('/\$databases\[.+\]/', file_get_contents($settings_local_url));
    }
}
