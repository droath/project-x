<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Database;
use Droath\ProjectX\Project\DrupalProjectType;
use Droath\ProjectX\ProjectX;
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

    public function testPackageDrupalBuild()
    {
        vfsStream::create([
            'config' => [
                'configure1.yml' => '',
                'configure2.yml' => ''
            ],
            'docroot' => [
                'index.php' => '',
                '.htaccess' => '',
                'robots.txt' => '',
                'update.php' => '',
                'web.config' => '',
                'modules' => [
                    'custom' => [
                        'page_manager' => []
                    ]
                ],
                'themes' => [
                    'custom' => [
                        'bootstrap' => []
                    ]
                ],
                'profile' => [
                    'custom' => [
                        'lighting' => []
                    ]
                ],
                'sites' => [
                    'default' => [
                        'settings.php' => '',
                        'settings.local.php' => ''
                    ]
                ]
            ],
            'salt.txt' => '12334567',
        ], $this->projectDir);

        $build_root = ProjectX::buildRoot();
        $this->drupalProject->packageDrupalBuild($build_root);
        $install_root = DrupalProjectType::INSTALL_ROOT;
        $this->assertFileExists("{$build_root}/{$install_root}/index.php");
        $this->assertFileExists("{$build_root}/{$install_root}/.htaccess");
        $this->assertFileExists("{$build_root}/{$install_root}/robots.txt");
        $this->assertFileExists("{$build_root}/{$install_root}/update.php");
        $this->assertFileExists("{$build_root}/{$install_root}/web.config");
        $this->assertFileExists("{$build_root}/{$install_root}/modules/custom/page_manager");
        $this->assertFileExists("{$build_root}/{$install_root}/themes/custom/bootstrap");
        $this->assertFileExists("{$build_root}/{$install_root}/profile/custom/lighting");
        $this->assertFileExists("{$build_root}/salt.txt");
        $this->assertFileExists("{$build_root}/{$install_root}/sites/default/settings.php");
        $this->assertFileExists("{$build_root}/config/configure1.yml");
    }

    public function testSetupDrush()
    {
        $this->drupalProject->setupDrush();
        $this->assertProjectFileExists('drush.wrapper');
        $this->assertTrue($this->projectDir->hasChild('drush'));
        $this->assertArrayHasKey('drush/drush', $this->drupalProject->getComposer()->getRequireDev());
    }

    public function testSetupDrushLocalAlias()
    {
        $this->drupalProject->setupDrushLocalAlias();

        $contents = file_get_contents(
            $this->getProjectFileUrl('drush/site-aliases/local.aliases.drushrc.php')
        );

        $this->assertProjectFileExists('drush/site-aliases/local.aliases.drushrc.php');
        $this->assertRegExp('/\$aliases\[\'project-x-test\'\]/', $contents);
        $this->assertRegExp('/\'uri\'\s?=>\s?\'local\.project-x-test\.com\',/', $contents);
        $this->assertRegExp('/\'root\'\s?=>\s?\'vfs:\/\/root\/docroot\'/', $contents);
    }

    public function testSetupDrushRemoteAliases()
    {
        $this->drupalProject->setupDrushRemoteAliases();

        $dev_content = file_get_contents(
            $this->getProjectFileUrl('drush/site-aliases/dev.aliases.drushrc.php')
        );
        $this->assertProjectFileExists('drush/site-aliases/dev.aliases.drushrc.php');
        $this->assertRegExp('/\$aliases\[\'development\'\]/', $dev_content);

        $stage_content = file_get_contents(
            $this->getProjectFileUrl('drush/site-aliases/stg.aliases.drushrc.php')
        );
        $this->assertProjectFileExists('drush/site-aliases/dev.aliases.drushrc.php');
        $this->assertRegExp('/\$aliases\[\'stage-one\'\]/', $stage_content);
        $this->assertRegExp('/\$aliases\[\'stage-two\'\]/', $stage_content);
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
        $this->assertProjectFileExists('docroot/profile/custom');
        $this->assertProjectFileExists('docroot/modules/custom');
        $this->assertProjectFileExists('docroot/modules/contrib');
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
        $this->assertRegExp('/\$config_directories\[CONFIG_SYNC_DIRECTORY].+\/config.+\;/', $settings_content);
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
        $this->assertRegExp('/\'host\'\s?=>\s?\'database\'\,/', $local_content);
        $this->assertRegExp('/\'port\'\s?=>\s?\'3307\'\,/', $local_content);
        $this->assertRegExp('/\'namespace\'\s?=>\s?\'.+mysql\'\,/', $local_content);
    }

    public function testSetupDrupalLocalSettingDatabaseOverride() {
        $this->drupalProject->setupDrupalLocalSettings((new Database())
            ->setProtocol('pgsql')
            ->setPort(5253)
            ->setHostname('127.0.0.1')
        );

        $local_url = $this->getProjectFileUrl('docroot/sites/default/settings.local.php');
        $local_content = file_get_contents($local_url);

        $this->assertRegExp('/\'host\'\s?=>\s?\'127.0.0.1\'\,/', $local_content);
        $this->assertRegExp('/\'port\'\s?=>\s?\'5253\'\,/', $local_content);
        $this->assertRegExp('/\'driver\'\s?=>\s?\'pgsql\'\,/', $local_content);
        $this->assertRegExp('/\'namespace\'\s?=>\s?\'.+pgsql\'\,/', $local_content);
    }

    public function testGetProjectOptionByKey()
    {
        $site = $this->drupalProject->getProjectOptionByKey('site');
        $this->assertEquals('Drupal-X Site', $site['name']);
        $this->assertEquals('standard', $site['profile']);
        $this->assertFalse($this->drupalProject->getProjectOptionByKey('nothing'));
    }

    public function testGetDatabaseInfo()
    {
        $this->assertEquals(new \ArrayObject([
            'host' => 'database',
            'port' => '3307',
            'username' => 'admin',
            'password' => 'root',
            'database' => 'drupal',
            'driver' => 'mysql',
        ]), $this->drupalProject->getDatabaseInfo()->asArray());
    }

    public function testGetDatabaseInfoWithOverrides() {
        $this->assertEquals(new \ArrayObject([
            'host' => '127.0.0.1',
            'port' => '5253',
            'username' => 'admin',
            'password' => 'root',
            'database' => 'drupal',
            'driver' => 'pgsql',
        ]), $this->drupalProject->getDatabaseInfoWithOverrides((new Database())
            ->setPort(5253)
            ->setProtocol('pgsql')
            ->setHostname('127.0.0.1')
        )->asArray());
    }

    public function testRemoveGitSubmoduleInVendor() {
        $this->drupalProject->setupProjectComposer();

        // Update composer.json file to declare a vendor dir.
        $composer = $this->drupalProject->getComposer();
        $composer->setConfig(['vendor-dir' => 'vendor1']);
        $this->drupalProject->saveComposer();

        // Build the directory structure that we're anticipating
        vfsStream::create([
            'build' => [
                'docroot' => [],
                'vendor1' => [
                    'symfony' => [
                        'package1' => [
                            'file1' => '',
                            'package_dir1' => [
                                '.git' => []
                            ],
                        ],
                        'package2' => [
                            'file1' => '',
                            'package_dir1' => [],
                        ],
                    ],
                    'guzzle' => [
                        'guzzle' => [
                            'file' => '',
                            '.git' => [],
                            'file2' => '',
                            'dir1' => [],
                        ],
                    ],
                ],
            ],
        ], $this->projectDir);

        $this->assertProjectFileExists('build');
        $this->assertProjectFileExists('build/vendor1/symfony/package1/package_dir1/.git');
        $this->assertProjectFileExists('build/vendor1/guzzle/guzzle/.git');

        $base_path = $this->projectRoot . '/build';
        $this
            ->drupalProject
            ->removeGitSubmodulesInVendor($base_path);

        $this->assertProjectFileExists('build/vendor1/symfony/package1/package_dir1/.git');
        $this->assertFileNotExists($base_path . '/vendor1/guzzle/guzzle.git');
    }

    public function testRemoveGitSubmoduleInInstallPath()
    {
        $this->drupalProject->setupProjectComposer();

        vfsStream::create([
            'build' => [
                'docroot' => [
                    'modules' => [
                        'contrib' => [
                            'google_tag' => [
                                'google_tag.info.yml' => '',
                                'google_tag.module' => '',
                                'includes' => [],
                                '.git' => []
                            ]
                        ]
                    ],
                    'themes' => [
                        'contrib' => [
                            'bootstrap' => [
                                'bootstrap.info.yml' => '',
                                'templates' => [],
                            ]
                        ]
                    ]
                ],
            ]
        ], $this->projectDir);

        $this->assertProjectFileExists('build');
        $this->assertProjectFileExists('build/docroot/modules/contrib/google_tag/.git');

        $base_path = $this->projectRoot . '/build';
        $this
            ->drupalProject
            ->removeGitSubmoduleInInstallPath($base_path);

        // Verify the .git directory was removed.
        $this->assertFileNotExists($base_path . '/docroot/modules/contrib/google_tag/.git');
    }

    public function testRemoveGitSubmodules()
    {
        vfsStream::create([
            'contrib' => [
                'module1' => [
                    '.gittest' => [],
                    '.gitignore' => '',
                    'module.info.yml' => ''
                ],
                'module2' => [
                    'module.info.yml' => '',
                    '.git' => [],
                ],
                '.git' => [],
            ]
        ], $this->projectDir);

        $this->assertProjectFileExists('contrib');
        $base_path = $this->projectRoot . '/contrib';
        $this->assertProjectFileExists('contrib/module2/.git');

        $this->drupalProject->removeGitSubmodules([$base_path]);
        $this->assertProjectFileExists('contrib/.git');

        // Verify the .git directory was removed.
        $this->assertFileNotExists($base_path . '/module2/.git');
    }

    public function testGetValidComposerInstallPaths()
    {
        $this->drupalProject->setupProjectComposer();

        vfsStream::create([
            'build' => [
                'docroot' => [
                    'modules' => [
                        'contrib' => []
                    ],
                    'themes' => [
                        'contrib' => []
                    ]
                ],
            ]
        ], $this->projectDir);

        $this->assertProjectFileExists('build');
        $base_path = $this->projectRoot . '/build';
        $installed_paths = $this
            ->drupalProject
            ->getValidComposerInstallPaths($base_path);

        $this->assertEquals([
            $base_path . '/docroot/modules/contrib',
            $base_path . '/docroot/themes/contrib',
        ], $installed_paths);
    }

    public function testRebuildSettings()
    {
        // Setup project using defined database information.
        $this->drupalProject->setupDrupalLocalSettings();

        // Update project configuration to reflect database update change.
        $config = ProjectX::getProjectConfig();
        $config->setOptions([
            'docker' => [
                'services' => [
                    'database' => [
                        'type' => 'postgres',
                        'ports' => [
                            '5431'
                        ],
                        'environment' => [
                            'POSTGRES_USER=admin2',
                            'POSTGRES_PASSWORD=root2',
                            "POSTGRES_DB=drupal2",
                            'PGDATA=/var/lib/postgresql/data'
                        ]
                    ]
                ]
            ]
        ]);
        $filename = $this->getProjectFileUrl($this->projectFileName);
        $config->save($filename);

        // Rebuild the settings based on new changes in configuration.
        $this->drupalProject->rebuildSettings();

        $settings_url = $this->getProjectFileUrl('docroot/sites/default/settings.local.php');
        $settings = file_get_contents($settings_url);

        $this->assertRegExp('/\'database\'\s?=>\s?\'drupal2\'\,/', $settings);
        $this->assertRegExp('/\'username\'\s?=>\s?\'admin2\'\,/', $settings);
        $this->assertRegExp('/\'password\'\s?=>\s?\'root2\'\,/', $settings);
        $this->assertRegExp('/\'port\'\s?=>\s?\'5431\'\,/', $settings);
        $this->assertRegExp('/\'host\'\s?=>\s?\'database\'\,/', $settings);
        $this->assertRegExp('/\'driver\'\s?=>\s?\'pgsql\'\,/', $settings);
        $this->assertRegExp('/\'namespace\'\s?=>\s?\'.+pgsql\'\,/', $settings);
    }
}
