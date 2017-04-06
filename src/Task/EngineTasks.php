<?php

namespace Droath\ProjectX\Task;

use Droath\HostsFileManager\HostsFile;
use Droath\HostsFileManager\HostsFileWriter;
use Droath\ProjectX\ProjectX;

/**
 * Define Project-X engine task commands.
 */
class EngineTasks extends TaskBase
{
    /**
     * Startup engine environment.
     *
     * @option $no-hostname Don't add hostname to the system hosts file
     *   regardless if it's defined in the project-x config.
     * @option $no-browser Don't open the browser window on startup regardless
     *   if it's defined in the project-x config.
     */
    public function engineUp($opts = ['no-hostname' => false, 'no-browser' => false])
    {
        $this->engineInstance()->up();

        // Allow projects to react to the engine startup.
        $this->projectInstance()->onEngineUp();

        // Add hostname to the system hosts file.
        if (!$opts['no-hostname']) {
            $this->addHostName($opts['no-browser']);
        }

        return $this;
    }

    /**
     * Shutdown engine environment.
     */
    public function engineDown()
    {
        $this->engineInstance()->down();

        // Allow projects to react to the engine shutdown.
        $this->projectInstance()->onEngineDown();

        // Remove hostname from the system hosts file.
        $this->removeHostName();

        return $this;
    }

    /**
     * Resume halted engine environment.
     */
    public function engineResume()
    {
        $this->engineInstance()->start();

        return $this;
    }

    /**
     * Restart engine environment.
     */
    public function engineRestart()
    {
        $this->engineInstance()->restart();

        return $this;
    }

    /**
     * Halt engine environment.
     */
    public function engineHalt()
    {
        $this->engineInstance()->suspend();
    }

    /**
     * Install engine configuration setup.
     */
    public function engineInstall()
    {
        $this->engineInstance()->install();

        return $this;
    }

    /**
     * Add hostname to hosts file.
     *
     * @param bool $no_browser
     *   Don't open the browser window.
     */
    protected function addHostName($no_browser)
    {
        $host = ProjectX::getProjectConfig()
            ->getHost();

        if (!empty($host)) {
            $hostsfile = (new HostsFile())
                ->setLine('127.0.0.1', $host['name']);

            (new HostsFileWriter($hostsfile))->add();

            $this->say(
                sprintf('Added %s to hosts file.', $host['name'])
            );

            if (!$no_browser && $host['open_on_startup'] == 'true') {
                $this->taskOpenBrowser('http://' . $host['name'])
                    ->run();
            }
        }

        return $this;
    }

    /**
     * Remove hostname from hosts file.
     */
    protected function removeHostName()
    {
        $host = ProjectX::getProjectConfig()
            ->getHost();

        if (!empty($host)) {
            $hostsfile = (new HostsFile())
                ->setLine('127.0.0.1', $host['name']);

            (new HostsFileWriter($hostsfile))->remove();

            $this->say(
                sprintf('Removed %s from hosts file.', $host['name'])
            );
        }

        return $this;
    }
}
