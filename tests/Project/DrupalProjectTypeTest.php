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

    public function testSetupDrupalFilesystem()
    {
        $directory = vfsStream::create([
            'docroot' => [
                'sites' => [
                    'default' => [
                        'default.settings.php' => '<?php print "settings";',
                    ],
                ],
            ],
        ], $this->projectDir);

        $this->drupalProject->setupDrupalFilesystem();

        $this->assertProjectFilePermission('0775', 'docroot/sites');
        $this->assertProjectFileExists('docroot/sites/default/files');
        $this->assertProjectFileExists('docroot/sites/default/settings.php');
    }

    public function testSetupDrupalSettings()
    {
        $directory = vfsStream::create([
            'docroot' => [
                'sites' => [
                    'default' => [
                        'settings.php' => "<?php print 'settings';\n\n",
                    ],
                ],
            ],
        ], $this->projectDir);

        $this->drupalProject->setupDrupalSettings();

        $settings_url = $this->getProjectFileUrl('docroot/sites/default/settings.php');

        $this->assertRegExp('/(include.+\/settings.local.php\'\;)\n\}/', file_get_contents($settings_url));
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
