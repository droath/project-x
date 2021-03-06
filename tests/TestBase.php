<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\Filesystem\YamlFilesystem;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Service\GitHubUserAuthStore;
use PHPUnit\Framework\TestCase;
use Robo\Robo;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Yaml\Yaml;
use org\bovigo\vfs\vfsStream;

/**
 * Define Project-X test base class.
 */
abstract class TestBase extends TestCase
{
    /**
     * Container object.
     *
     * @var \League\Container\ContainerInterface
     */
    protected $container;

    /**
     * Project-X.
     *
     * @var \Droath\ProjectX\ProjectX
     */
    protected $projectX;

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
        $this->projectX = new ProjectX();
        $this->projectDir = vfsStream::setup('root');
        $this->projectRoot = vfsStream::url('root');

        $this->container = Robo::createDefaultContainer(
            null,
            new NullOutput(),
            $this->projectX
        );
        $this->addProjectXConfigToRoot();
        $project_path = $this->getProjectXFilePath();

        ProjectX::clearProjectConfig();

        ProjectX::setProjectPath($project_path);
        ProjectX::setDefaultServices($this->container);
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
    protected function addProjectXLocalConfigToRoot()
    {
        (new YamlFilesystem(
            $this->getProjectXLocalFileContents(),
            $this->projectRoot
        ))
        ->save($this->projectLocalFileName);

        return $this;
    }

    /**
     * Add GitHub user authentication file.
     */
    protected function addGithubUserAuthFile()
    {
        $contents = Yaml::dump(
            $this->getGitHubUserAuthContents()
        );

        vfsStream::create([
            GitHubUserAuthStore::FILE_DIR => [
                GitHubUserAuthStore::FILE_NAME => $contents,
            ],
        ],
        $this->projectDir);

        return $this;
    }

    /**
     * Get GitHub user authentication contents.
     *
     * @return array
     */
    protected function getGitHubUserAuthContents()
    {
        return [
            'user' => 'test-user',
            'token' => '3253523452352',
        ];
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
            'version' => 8,
            'engine' => 'docker',
            'root' => '/www',
            'remote' => [
                'environments' => [
                    [
                        'realm' => 'dev',
                        'name' => 'Development',
                        'path' => '/var/www/html/docroot',
                        'uri' => 'dev.project-x.com',
                        'ssh_url' => 'dev-user@my-hosting.com',
                    ],
                    [
                        'realm' => 'stg',
                        'name' => 'Stage One',
                        'path' => '/var/www/html/docroot',
                        'uri' => 'stage.project-x.com',
                        'ssh_url' => 'stage-user@my-hosting.com',
                    ],
                    [
                        'realm' => 'stg',
                        'name' => 'stage-two',
                        'path' => '/var/www/html/docroot',
                        'uri' => 'stage2.project-x.com',
                        'ssh_url' => 'stage2-user@my-hosting.com',
                    ]
                ]
            ],
            'github' => [
                'url' => 'https://github.com/droath/project-x',
            ],
            'host' => [
                'name' => 'local.project-x-test.com',
                'open_on_startup' => 'true',
            ],
            'network' => [
                'proxy' => 'true',
            ],
            'options' => [
                'drupal' => [
                    'site' => [
                        'name' => 'Drupal-X Site',
                        'profile' => 'standard',
                    ],
                    'account' => [
                        'mail' => 'admin@example-test.com',
                        'name' => 'admin-testing',
                        'pass' => 'pass-testing',
                    ],
                ],
                'docker' => [
                    'services' => [
                        'web' => [
                            'type' => 'apache',
                            'version' => '2.4',
                        ],
                        'web2' => [
                            'type' => 'nginx',
                            'version' => '1.11'
                        ],
                        'php' => [
                            'type' => 'php',
                            'version' => 7.2,
                            'packages' => ['nmap'],
                            'extensions' => ['soap'],
                            'pecl_packages' => ['Event_Dispatcher:1.1.0'],
                            'commands' => [
                                'mv /var/html/test.html /var/html/project.html'
                            ]
                        ],
                        'database' => [
                            'type' => 'mysql',
                            'version' => 'latest',
                            'ports' => ['3307:3307'],
                            'environment' => [
                                'MYSQL_USER=admin',
                                'MYSQL_PASSWORD=root',
                                'MYSQL_DATABASE=drupal',
                                'MYSQL_ALLOW_EMPTY_PASSWORD=1'
                            ]
                        ],
                        'database2' => [
                            'type' => 'mariadb',
                            'version' => '5.5',
                            'environment' => [
                                'MYSQL_USER=admin',
                                'MYSQL_PASSWORD=root',
                                'MYSQL_DATABASE=drupal',
                                'MYSQL_ALLOW_EMPTY_PASSWORD=1'
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * The project-X local file contents fixture.
     *
     * @return array
     */
    protected function getProjectXLocalFileContents()
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
     * Get project file contents.
     *
     * @param string $filename
     *   The project filename.
     *
     * @return bool|string
     */
    protected function getProjectFileContents($filename)
    {
        return file_get_contents(
            $this->getProjectFileUrl($filename)
        );
    }

    /**
     * Add composer plugin structure.
     */
    protected function addComposerPluginStructure()
    {
        $contents = json_encode([
            [
                'name' => 'droath/platform-example',
                'type' => 'project-x',
                'autoload' => [
                    'psr-4' => [
                        'Droath\\PlatformExample\\' => './src'
                    ],
                ],
            ],
            [
                'name' => 'droath/project-example',
                'type' => 'project-x',
                'autoload' => [
                    'psr-4' => [
                        'Droath\\ProjectExample\\' => './src'
                    ],
                ],
            ],
            [
                'name' => 'droath/engine-example',
                'type' => 'project-x',
                'autoload' => [
                    'psr-4' => [
                        'Droath\\EngineExample\\' => './src'
                    ],
                ],
            ],
        ]);
        $engine_path = APP_ROOT . '/tests/fixtures/TestEngineType.php';
        $project_path = APP_ROOT . '/tests/fixtures/TestProjectType.php';
        $platform_path = APP_ROOT . '/tests/fixtures/TestPlatformType.php';

        vfsStream::create([
            'vendor' => [
                'composer' => [
                    'installed.json' => $contents
                ],
                'droath' => [
                    'platform-example' => [
                        'src' => [
                            'Platform' => [
                                'TestPlatformType.php' => file_get_contents($platform_path)
                            ]
                        ]
                    ],
                    'project-example' => [
                        'src' => [
                            'Project' => [
                                'TestProjectType.php' => file_get_contents($project_path)
                            ]
                        ]
                    ],
                    'engine-example' => [
                        'src' => [
                            'Engine' => [
                                'TestEngineType.php' => file_get_contents($engine_path)
                            ]
                        ]
                    ]
                ]
            ]
        ], $this->projectDir);

        require_once $engine_path;
        require_once $project_path;
        require_once $platform_path;
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
