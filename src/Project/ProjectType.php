<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\EngineTrait;
use Droath\ProjectX\Event\DeployEventInterface;
use Droath\ProjectX\PlatformTrait;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubType;

/**
 * Define Project-X project type.
 */
abstract class ProjectType extends TaskSubType implements ProjectTypeInterface, DeployEventInterface
{
    use EngineTrait;
    use PlatformTrait;

    /**
     * Project default install root.
     */
    const INSTALL_ROOT = '/docroot';

    /**
     * Project build abort state.
     */
    const BUILD_ABORT = 0;

    /**
     * Project build fresh state.
     */
    const BUILD_FRESH = 1;

    /**
     * Project build dirty state.
     */
    const BUILD_DIRTY = 2;

    /**
     * Project default version.
     */
    const DEFAULT_VERSION = 0;

    /**
     * Project support versions.
     */
    const SUPPORTED_VERSIONS = [];

    /**
     * Project current install root.
     */
    public static function installRoot()
    {
        $install_root = ProjectX::getProjectConfig()
            ->getRoot();

        // Ensure a forward slash has been added to the install root.
        $install_root = substr($install_root, 0, 1) != '/'
            ? "/{$install_root}"
            : $install_root;

        if (isset($install_root) && !empty($install_root)) {
            return $install_root;
        }

        return static::INSTALL_ROOT;
    }

    /**
     * Get project current install root.
     *
     * @param bool $strip_slash
     *   Strip the the beginning slash from the install root.
     *
     * @return string
     */
    public function getInstallRoot($strip_slash = false)
    {
        $install_root = static::installRoot();

        return $strip_slash === false
            ? $install_root
            : substr($install_root, 1);
    }

    /**
     * {@inheritdoc}
     */
    public function setupNewProject()
    {
        $this->say('Setup process for a fresh exciting new project has begun. 🤘');
        $this->io()->newLine();

        $this->installEnvEngine();
    }

    /**
     * {@inheritdoc}
     */
    public function setupExistingProject(
        $no_engine = false,
        $restore_method = null,
        $no_browser = false,
        $localhost = false
    ) {
        $this->say('Setup process for an old rusty project has begun. 🤮');
        $this->io()->newLine();

        $engine = $this->getEngineInstance();

        if (!$engine->isEngineInstalled()) {
            $this->installEnvEngine();
        }
    }

    /**
     * React on the deploy build.
     *
     * @param $build_root
     *   The build root directory.
     */
    public function onDeployBuild($build_root)
    {
        $install_root = $build_root . static::installRoot();

        if (!file_exists($install_root)) {
            $this->_mkdir($install_root);
        }
    }

    /**
     * Run the engine up command.
     *
     * @return self
     */
    public function projectEnvironmentUp()
    {
        $this->taskSymfonyCommand($this->getAppCommand('env:up'))
            ->opt('no-browser')
            ->run();

        return $this;
    }

    /**
     * Install environment engine.
     *
     * @return self
     */
    public function installEnvEngine()
    {
        $this->getEngineInstance()->install();

        return $this;
    }

    /**
     * Setup project filesystem.
     *
     * The setup process consist of the following:
     *   - Update project root permission.
     *   - Make project install directory.
     *
     * @return self
     */
    public function setupProjectFilesystem()
    {
        $this->taskFilesystemStack()
            ->chmod(ProjectX::projectRoot(), 0775)
            ->mkdir($this->getInstallPath(), 0775)
            ->run();

        return $this;
    }

    /**
     * Project launch browser.
     *
     * @param string $schema
     *   The URL schema.
     * @param int $delay
     *   The startup delay in seconds.
     *
     * @return self
     */
    public function projectLaunchBrowser($schema = 'http', $delay = 10)
    {
        sleep($delay);

        $this->taskOpenBrowser("{$schema}://{$this->getProjectHostname()}")
            ->run();

        return $this;
    }

    /**
     * Is project built and not empty.
     *
     * @return bool
     */
    public function isBuilt()
    {
        return $this->isDirEmpty($this->getInstallPath());
    }
    
    /**
     * Get project option by key.
     *
     * @param string $key
     *   The unique key for the option.
     *
     * @return mixed|bool
     *   The set value for the given project option key; otherwise FALSE.
     */
    public function getProjectOptionByKey($key)
    {
        $options = $this->getProjectOptions();

        if (!isset($options[$key])) {
            return false;
        }

        return $options[$key];
    }

    /**
     * Get project install path.
     *
     * @return string
     *   The full path to the project install root.
     */
    public function getInstallPath()
    {
        return ProjectX::projectRoot() . static::installRoot();
    }

    /**
     * Get project version.
     *
     * @return string
     *   The project version defined in the project-x config; otherwise set to
     *   the project default version.
     */
    public function getProjectVersion()
    {
        return ProjectX::getProjectConfig()->getVersion()
            ?: static::DEFAULT_VERSION;
    }

    /**
     * Rebuild project settings.
     */
    public function rebuildSettings()
    {
        // Nothing to do at parent level.
    }

    /**
     * Can project run it's install process.
     */
    protected function canInstall()
    {
        return $this->isBuilt();
    }

    /**
     * Can project run it's build process.
     *
     * @return int
     *   Return the build status.
     */
    protected function canBuild()
    {
        $rebuild = false;

        if ($this->isBuilt()) {
            $rebuild = $this->askConfirmQuestion(
                'Project has already been built, do you want to rebuild?',
                false
            );

            if (!$rebuild) {
                return static::BUILD_ABORT;
            }
        }

        return !$rebuild ? static::BUILD_FRESH : static::BUILD_DIRTY;
    }

    /**
     * Determine if directory is empty.
     *
     * @param $directory
     *   The path to directory.
     *
     * @return bool
     */
    protected function isDirEmpty($directory)
    {
        return is_dir($directory)
            && file_exists($directory)
            && (new \FilesystemIterator($directory))->valid();
    }

    /**
     * Get application command.
     *
     * @param string $name
     *   The name of the command.
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function getAppCommand($name)
    {
        return $this->getApplication()->find($name);
    }

    /**
     * Get host checker service.
     *
     * @return \Droath\ProjectX\Service\HostChecker
     *   The host check object.
     */
    protected function getHostChecker()
    {
        return $this->getContainer()->get('projectXHostChecker');
    }

    /**
     * Get project hostname.
     *
     * @return string
     *   The project hostname defined in project-x config; otherwise localhost.
     */
    protected function getProjectHostname()
    {
        $host = ProjectX::getProjectConfig()
            ->getHost();

        return isset($host['name']) ? $host['name'] : 'localhost';
    }

    /**
     * Get project options.
     *
     * @return array
     *   An array of project options defined in the Project-X configuration.
     */
    protected function getProjectOptions()
    {
        $config = ProjectX::getProjectConfig();

        $type = $config->getType();
        $options = $config->getOptions();

        return isset($options[$type])
            ? $options[$type]
            : [];
    }

    /**
     * Delete project install directory.
     *
     * @return self
     */
    protected function deleteInstallDirectory()
    {
        $this->taskDeleteDir($this->getInstallPath())->run();

        return $this;
    }
}
