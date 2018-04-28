<?php

namespace Droath\ProjectX\Task;

use Droath\HostsFileManager\HostsFile;
use Droath\HostsFileManager\HostsFileWriter;
use Droath\ProjectX\ProjectX;

/**
 * Define Project-X environment task commands.
 */
class EnvTasks extends TaskBase
{
    /**
     * Startup environment engine.
     *
     * @param array $opts An array of command options.
     * @option $no-hostname Don't add hostname to the system hosts file
     *   regardless if it's defined in the project-x config.
     * @option $no-browser Don't open the browser window on startup regardless
     *   if it's defined in the project-x config.
     *
     * @return EnvTasks
     * @throws \Exception
     */
    public function envUp($opts = ['no-hostname' => false, 'no-browser' => false])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $status = $this->engineInstance()->up();

        if ($status !== false) {
            // Allow projects to react to the engine startup.
            $this->projectInstance()->onEngineUp();

            // Add hostname to the system hosts file.
            if (!$opts['no-hostname']) {
                $this->addHostName($opts['no-browser']);
            }
        }
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Rebuild environment engine configurations.
     */
    public function envRebuild()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->rebuild();
        $this->projectInstance()->rebuildSettings();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Shutdown environment engine.
     */
    public function envDown()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->down();

        // Allow projects to react to the engine shutdown.
        $this->projectInstance()->onEngineDown();

        // Remove hostname from the system hosts file.
        $this->removeHostName();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Resume environment engine.
     */
    public function envResume()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->start();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Restart environment engine.
     */
    public function envRestart()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->restart();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Reboot environment engine.
     */
    public function envReboot()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->reboot();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Halt environment engine.
     */
    public function envHalt()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->suspend();
        $this->executeCommandHook(__FUNCTION__, 'after');
    }

    /**
     * Install environment engine configurations.
     */
    public function envInstall()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->install();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Add hostname to hosts file.
     *
     * @param bool $no_browser
     *   Don't open the browser window.
     *
     * @return self
     * @throws \Exception
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
