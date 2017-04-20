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
        vfsStream::create([
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

    public function testSetupProjectComposer()
    {
        $composer_json = json_encode([
            'require' => [
                'drupal/devel' => '^8.1'
            ]
        ]);

        vfsStream::create([
            'project-x' => [
                'composer.json' => $composer_json
            ]
        ], $this->projectDir);

        $this->drupalProject->setupProjectComposer();
        $composer = $this->drupalProject
            ->getComposer();

        $this->assertEquals('project', $composer->getType());
        $this->assertTrue($composer->getPreferStable());
        $this->assertEquals('dev', $composer->getMinimumStability());
        $this->assertArrayHasKey('drupal', $composer->getRepositories());
        $this->assertArrayHasKey('drupal/core', $composer->getRequire());
        $this->assertArrayHasKey('drupal/devel', $composer->getRequire());
        $this->assertArrayHasKey('drupal-scaffold', $composer->getExtra());
        $this->assertArrayHasKey('installer-paths', $composer->getExtra());
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
        $this->assertArrayHasKey('drush/drush', $this->drupalProject->getComposer()->getRequireDev());
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
        vfsStream::create([
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
        $this->drupalProject
            ->setupDrupalLocalSettings(
                'drupal-test',
                'drupal-admin',
                'drupal-pass',
                'drupal-host',
                false
            );
        $local_url = $this->getProjectFileUrl('docroot/sites/default/settings.local.php');
        $local_content = file_get_contents($local_url);

        $this->assertProjectFileExists('docroot/sites/default/settings.local.php');
        $this->assertRegExp('/\$databases\[.+\]/', $local_content);
        $this->assertRegExp('/\'database\'\s?=>\s?\'drupal-test\'\,/', $local_content);
        $this->assertRegExp('/\'username\'\s?=>\s?\'drupal-admin\'\,/', $local_content);
        $this->assertRegExp('/\'password\'\s?=>\s?\'drupal-pass\'\,/', $local_content);
        $this->assertRegExp('/\'host\'\s?=>\s?\'drupal-host\'\,/', $local_content);
    }

    public function testSetupDrupalLocalSettingsDefault()
    {
        $this->drupalProject->setupDrupalLocalSettings();

        $local_url = $this->getProjectFileUrl('docroot/sites/default/settings.local.php');
        $local_content = file_get_contents($local_url);

        $this->assertProjectFileExists('docroot/sites/default/settings.local.php');
        $this->assertRegExp('/\$databases\[.+\]/', $local_content);
        $this->assertRegExp('/\'database\'\s?=>\s?\'drupal\'\,/', $local_content);
        $this->assertRegExp('/\'username\'\s?=>\s?\'admin\'\,/', $local_content);
        $this->assertRegExp('/\'password\'\s?=>\s?\'root\'\,/', $local_content);
        $this->assertRegExp('/\'host\'\s?=>\s?\'mysql\'\,/', $local_content);
    }
}
