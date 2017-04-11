<?php

namespace Droath\ProjectX;

use Droath\ProjectX\Config\ProjectXConfig;
use Droath\ProjectX\Discovery\PhpClassDiscovery;
use League\Container\ContainerAwareTrait;
use Robo\Robo;
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

/**
 * Project-X console CLI application.
 */
class ProjectX extends Application
{
    use ContainerAwareTrait;

    /**
     * Application name.
     */
    const APP_NAME = 'Project-X';

    /**
     * Project-X project path.
     *
     * @var string
     */
    protected static $projectPath;

    /**
     * Project-X project config.
     *
     * @var \Droath\ProjectX\Config\ProjectXConfig
     */
    protected static $projectConfig;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($this->printBanner(), $this->printVersion());
    }

    /**
     * Discover Project-X commands.
     */
    public function discoverCommands()
    {
        $commands = (new PhpClassDiscovery())
            ->addSearchLocation(APP_ROOT . '/src/Command')
            ->matchExtend('Symfony\Component\Console\Command\Command')
            ->discover();

        foreach ($commands as $classname) {
            $this->add(new $classname());
        }

        return $this;
    }

    /**
     * Set Project-X project path.
     *
     * @param string $project_path
     *   The path to the project-x configuration.
     */
    public static function setProjectPath($project_path)
    {
        self::$projectPath = $project_path;
    }

    /**
     * Get Project-X container object.
     *
     * @return \League\Container\ContainerInterface
     */
    public static function getContainer()
    {
        return Robo::getContainer();
    }

    /**
     * Set default container services.
     */
    public static function setDefaultServices($container)
    {
        $container
            ->add('projectXComposer', \Droath\ProjectX\Composer::class)
            ->withArgument('projectXTemplate');
        $container
            ->share('projectXGitHubUserAuth', \Droath\ProjectX\Service\GitHubUserAuthStore::class);
        $container
            ->share('projectXTemplate', \Droath\ProjectX\Template\TemplateManager::class);
        $container
            ->add('projectXHostChecker', \Droath\ProjectX\Service\HostChecker::class);
        $container
            ->add('projectXEngine', function () {
                return (new \Droath\ProjectX\Engine\EngineTypeFactory())
                    ->createInstance();
            });
        $container
            ->add('projectXProject', function () {
                return (new \Droath\ProjectX\Project\ProjectTypeFactory())
                    ->createInstance();
            });
    }

    /**
     * Get client hostname.
     *
     * @return string
     *   The client hostname.
     */
    public static function clientHostName()
    {
        return gethostname();
    }

    /**
     * Get client hostname IP.
     *
     * @return string
     *   The client hostname IP address.
     */
    public static function clientHostIP()
    {
        return getHostByName(static::clientHostName());
    }

    /**
     * Project task locations.
     *
     * @return array
     *   An array of locations
     */
    public static function taskLocations()
    {
        $locations = [
            self::projectRoot(),
        ];
        $locations[] = self::getProjectType()
            ->taskDirectory();

        if (self::hasProjectConfig()) {
            $locations[] = APP_ROOT . '/src/Task';
        }

        return array_filter($locations);
    }

    /**
     * Get project root path.
     *
     * @return string
     */
    public static function projectRoot()
    {
        return static::hasProjectConfig()
            ? dirname(self::$projectPath)
            : getcwd();
    }

    /**
     * Has Project-X configuration.
     *
     * @return bool
     */
    public static function hasProjectConfig()
    {
        return file_exists(self::$projectPath);
    }

    /**
     * Clear Project-X configuration.
     */
    public static function clearProjectConfig()
    {
        self::$projectConfig = null;
    }

    /**
     * Get project type instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    public static function getProjectType()
    {
        return self::getContainer()
            ->get('projectXProject');
    }

    /**
     * Get Project-X configuration.
     *
     * @return \Droath\ProjectX\Config\ProjectXConfig
     */
    public static function getProjectConfig()
    {
        if (!isset(self::$projectConfig)) {
            $config = self::getConfigInstance();
            $values = self::getLocalConfigValues();

            self::$projectConfig = empty($values)
                ? $config
                : $config->update($values);
        }

        return self::$projectConfig;
    }

    /**
     * Get Project-X project machine-name.
     *
     * @return string
     */
    public function getProjectMachineName()
    {
        $config = self::getProjectConfig();

        return Utility::machineName($config->getName());
    }

    /**
     * Get configuration instance.
     *
     * @return \Droath\ProjectX\Config\ProjectXConfig
     */
    protected static function getConfigInstance()
    {
        if (!self::hasProjectConfig()) {
            return new ProjectXConfig();
        }

        return ProjectXConfig::createFromFile(
            new \SplFileInfo(self::$projectPath)
        );
    }

    /**
     * Get local configuration values.
     *
     * @return array
     */
    protected static function getLocalConfigValues()
    {
        $root = self::projectRoot();
        $path = "{$root}/project-x.local.yml";

        if (!file_exists($path)) {
            return [];
        }

        $instance = ProjectXConfig::createFromFile(
            new \SplFileInfo($path)
        );

        return array_filter($instance->toArray());
    }

    /**
     * Print application version.
     */
    private function printVersion()
    {
        return file_get_contents(
            dirname(__DIR__) . '/VERSION'
        );
    }

    /**
     * Print application banner.
     */
    private function printBanner()
    {
        $filename = dirname(__DIR__) . '/banner.txt';

        if (!file_exists($filename)) {
            return static::APP_NAME;
        }

        return file_get_contents($filename);
    }
}
