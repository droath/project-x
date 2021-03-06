<?php

namespace Droath\ProjectX\Task;

use Droath\HostsFileManager\HostsFile;
use Droath\HostsFileManager\HostsFileWriter;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\EngineType;
use Droath\ProjectX\Event\EngineEventInterface;
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
     * @option $native Run the environment engine in native mode.
     * @option $no-hostname Don't add hostname to the system hosts file
     *   regardless if it's defined in the project-x config.
     * @option $no-browser Don't open the browser window on startup regardless
     *   if it's defined in the project-x config.
     *
     * @return EnvTasks
     * @throws \Exception
     */
    public function envUp($opts = [
        'native' => false,
        'no-hostname' => false,
        'no-browser' => false
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        /** @var EngineType $engine */
        $engine = $this->engineInstance();

        if ($engine instanceof DockerEngineType) {
            if ($opts['native']) {
                $engine->disableDockerSync();
            }
        }
        $status = $engine->up();

        if ($status !== false) {
            $this->invokeEngineEvent('onEngineUp');

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
     *
     * @param array $opts
     * @option $include-network Shutdown the shared network proxy.
     *
     * @return EnvTasks
     */
    public function envDown($opts = [
        'include-network' => false,
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->down($opts['include-network']);

        // Allow projects to react to the engine shutdown.
        $this->invokeEngineEvent('onEngineDown');

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
     *
     * @param array $opts
     * @option $include-network Reboot the shared network proxy.
     *
     * @return EnvTasks
     */
    public function envReboot($opts = [
        'include-network' => false,
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->reboot($opts['include-network']);
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
     * SSH into the environment engine.
     *
     * @param array $opts An array of command options.
     * @option string $service The service name on which to ssh into.
     *
     * @return $this
     */
    public function envSsh($opts = ['service' => null])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->ssh($opts['service']);
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Display logs for the environment engine.
     *
     * @param array $opts An array of command options.
     * @option bool $follow Determine if we should follow the log output.
     * @option string $show Set all or a numeric value on how many lines to
     * output.
     * @option string $service The service name on which to show the logs for.
     *
     * @return $this
     */
    public function envLogs($opts = ['show' => 'all', 'follow' => false, 'service' => null])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->logs($opts['show'], $opts['follow'], $opts['service']);
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Execute an arbitrary command in the environment engine.
     *
     * @param array $execute_command The commend string to execute.
     * @param array $opts An array of the command options
     * @option string $service The service name on which to execute the
     * command inside the container.
     *
     * @return $this
     */
    public function envExec(array $execute_command, $opts = ['service' => null])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->engineInstance()->exec(
            implode(' ', $execute_command),
            $opts['service']
        );
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

    /**
     * Invoke the engine event.
     *
     * @param $method
     *   The engine event method.
     */
    protected function invokeEngineEvent($method)
    {
        $project = $this->getProjectInstance();
        if ($project instanceof EngineEventInterface) {
            call_user_func_array([$project, $method], []);
        }

        $platform = $this->getPlatformInstance();
        if ($platform instanceof EngineEventInterface) {
            call_user_func_array([$platform, $method], []);
        }
    }
}
