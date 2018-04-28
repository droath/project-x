<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\ProjectX;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Define Project-X engine task commands.
 */
class EngineTasks extends TaskBase
{
    /**
     * Startup engine environment.
     *
     * @param array $opts An array of command options.
     * @option $no-hostname Don't add hostname to the system hosts file
     *   regardless if it's defined in the project-x config.
     * @option $no-browser Don't open the browser window on startup regardless
     *   if it's defined in the project-x config.
     *
     * @hidden
     * @deprecated
     *
     * @return EngineTasks
     * @throws \Exception
     */
    public function engineUp($opts = ['no-hostname' => false, 'no-browser' => false])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:up');
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Rebuild engine configuration.
     *
     * @hidden
     * @deprecated
     */
    public function engineRebuild()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:rebuild');
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Shutdown engine environment.
     *
     * @hidden
     * @deprecated
     */
    public function engineDown()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:down');
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Resume halted engine environment.
     *
     * @hidden
     * @deprecated
     */
    public function engineResume()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:resume');
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Restart engine environment.
     *
     * @hidden
     * @deprecated
     */
    public function engineRestart()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:restart');
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Reboot engine environment.
     *
     * @hidden
     * @deprecated
     */
    public function engineReboot()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:reboot');
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Halt engine environment.
     *
     * @hidden
     * @deprecated
     */
    public function engineHalt()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:halt');
        $this->executeCommandHook(__FUNCTION__, 'after');
    }

    /**
     * Install engine configuration setup.
     *
     * @hidden
     * @deprecated
     */
    public function engineInstall()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->executeExistingCommand('env:install');
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Execute existing command.
     *
     * @param $command_name
     *   The name of the command.
     *
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     *
     * @return int
     *   The execute command exit code.
     * @throws \Exception
     */
    protected function executeExistingCommand(
        $command_name,
        InputInterface $input = null,
        OutputInterface $output = null
    ) {
        if (!isset($command_name)) {
            return 1;
        }
        $input = isset($input) ? $input : $this->input();
        $output = isset($output) ? $output : $this->output();

        return $this->getApplication()
            ->find($command_name)
            ->run($input, $output);
    }

    /**
     * Get the CLI application object.
     *
     * @return ProjectX
     *   The application object.
     */
    protected function getApplication()
    {
        return $this->container->get('application');
    }
}
