<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubType;

/**
 * Define Project-X project type.
 */
abstract class ProjectType extends TaskSubType implements ProjectTypeInterface
{
    /**
     * Project install root.
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
     * Project type supports docker.
     *
     * @var bool
     */
    protected $supportsDocker = false;

    /**
     * Project supports docker.
     *
     * @return self
     */
    public function supportsDocker()
    {
        $this->supportsDocker = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $this->say('The project build process has begun. 🤘');
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->projectEngineInstall();
        $this->say('The project install process has begun. 🤘');
    }

    /**
     * React on the engine startup.
     */
    public function onEngineUp()
    {
        // Nothing to do at the parent level.
    }

    /**
     * React on the engine shutdown.
     */
    public function onEngineDown()
    {
        // Nothing to do at the parent level.
    }

    /**
     * Has docker support.
     */
    public function hasDockerSupport()
    {
        return $this->supportsDocker;
    }

    /**
     * Run the engine up command.
     *
     * @return self
     */
    public function projectEngineUp()
    {
        $this->taskSymfonyCommand($this->getAppCommand('engine:up'))
            ->opt('no-browser')
            ->run();

        return $this;
    }

    /**
     * Run the engine install command.
     *
     * @return self
     */
    public function projectEngineInstall()
    {
        $this->taskSymfonyCommand($this->getAppCommand('engine:install'))
            ->run();

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
        return is_dir($this->getInstallPath())
            && (new \FilesystemIterator($this->getInstallPath()))->valid();
    }

    /**
     * Get project install path.
     *
     * @return string
     *   The full path to the project install root.
     */
    public function getInstallPath()
    {
        return ProjectX::projectRoot() . static::INSTALL_ROOT;
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
     * Check if host has database connection.
     *
     * @param string $host
     *   The database hostname.
     * @param int $port
     *   The database port.
     * @param int $seconds
     *   The amount of seconds to continually check.
     *
     * @return bool
     *   Return true if the database is connectible; otherwise false.
     */
    protected function hasDatabaseConnection($host, $port = 3306, $seconds = 30)
    {
        $hostChecker = $this->getHostChecker();
        $hostChecker
            ->setHost($host)
            ->setPort($port);

        return $hostChecker->isPortOpenRepeater($seconds);
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
