<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\TaskSubType;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        // Noting to do at the parent level.
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->projectEngineInstall();
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
     * Has project been built and is not empty.
     *
     * @return bool
     */
    protected function isBuilt()
    {
        return is_dir($this->getInstallPath())
            && (new \FilesystemIterator($this->getInstallPath()))->valid();
    }

    /**
     * Ask confirmation question.
     *
     * @param string $text
     *   The question text.
     * @param bool $default
     *   The default value.
     *
     * @return bool
     */
    protected function askConfirmQuestion($text, $default = false)
    {
        $default_text = $default ? 'yes' : 'no';
        $question = "☝️  $text (y/n) [$default_text] ";

        return $this->doAsk(new ConfirmationQuestion($question, $default));
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
        $config = $this->getProjectXConfig();

        return isset($config['host']) ? $config['host']['name'] : 'localhost';
    }

    /**
     * Get project install path.
     *
     * @return string
     *   The full path to the project install root.
     */
    protected function getInstallPath()
    {
        return $this->getProjectXRootPath() . static::INSTALL_ROOT;
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
