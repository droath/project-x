<?php

namespace Droath\ProjectX\Task;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Droath\ProjectX\CommandBuilder;
use Droath\ProjectX\CommandHook;
use Droath\ProjectX\CommandHookInterface;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\EngineTypeInterface;
use Droath\ProjectX\EngineTrait;
use Droath\ProjectX\Exception\CommandHookRuntimeException;
use Droath\ProjectX\ProjectTrait;
use Droath\ProjectX\ProjectX;
use Robo\Contract\TaskInterface;
use Robo\Tasks;

/**
 * Event task base object.
 */
abstract class EventTaskBase extends Tasks
{
    use EngineTrait;
    use ProjectTrait;

    /**
     * Execute command hook.
     *
     * @param $method
     *   The task method command.
     * @param $event_type
     *   The event type on which to execute.
     *
     * @return $this
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function executeCommandHook($method, $event_type)
    {
        foreach ($this->getCommandsByMethodEventType($method, $event_type) as $command) {
            if (is_string($command)) {
                $command = (new CommandHook())
                    ->setType('raw')
                    ->setCommand($command);
            } elseif (is_array($command)) {
                $command = CommandHook::createWithData($command);
            }

            if (!$command instanceof CommandHookInterface) {
                continue;
            }

            switch($command->getType()) {
                case 'symfony':
                    $this->executeSymfonyCmdHook($command, $method);
                    break;
                default:
                    $this->executeEngineCmdHook($command);
                    break;

            }
        }

        return $this;
    }

    /**
     * Execute engine command hook.
     *
     * @param CommandHookInterface $hook_command
     *   The command hook object.
     *
     * @return \Robo\Result|\Robo\ResultData
     */
    protected function executeEngineCmdHook(CommandHookInterface $hook_command)
    {
        $options = $hook_command->getOptions();

        // Determine the service the command should be ran inside.
        $service = isset($options['service'])
            ? $options['service']
            : null;

        // Determine if the command should be ran locally.
        $localhost = (boolean) isset($options['localhost'])
            ? $options['localhost']
            : !isset($service);

        $command = $this->resolveHookCommand($hook_command);

        return $this->executeEngineCommand($command, $service, [], false, $localhost);
    }

    /**
     * Resolve hook command.
     *
     * @param CommandHookInterface $command_hook
     *   The command hook object.
     *
     * @return bool|CommandBuilder
     */
    protected function resolveHookCommand(CommandHookInterface $command_hook)
    {
        $command = null;

        switch ($command_hook->getType()) {
            case 'raw':
                $command = $command_hook->getCommand();
                break;
            case 'engine':
                $command = $this->getEngineCommand($command_hook);
                break;
            case 'project':
                $command = $this->getProjectCommand($command_hook);
                break;
        }

        return isset($command) ? $command : false;
    }

    /**
     * Execute symfony command hook.
     *
     * @param CommandHookInterface $command_hook
     * @param $method
     *
     * @return \Robo\Result
     */
    protected function executeSymfonyCmdHook(CommandHookInterface $command_hook, $method)
    {
        $command = $command_hook->getCommand();

        $container = ProjectX::getContainer();
        $application = $container->get('application');

        try {
            $info = $this->getCommandInfo($method);
            $command = $application->find($command);

            if ($command->getName() === $info->getName()) {
                throw new \Exception(sprintf(
                    'Unable to call the %s command hook due to it ' .
                    'invoking the parent method.',
                    $command->getName()
                ));
            }
        } catch (CommandNotFoundException $exception) {
            throw new CommandHookRuntimeException(sprintf(
                'Unable to find %s command in the project-x command ' .
                'hook.',
                $command
            ));
        } catch (\Exception $exception) {
            throw new CommandHookRuntimeException($exception->getMessage());
        }
        $exec = $this->taskSymfonyCommand($command);
        $definition = $command->getDefinition();

        // Support symfony command options.
        if ($command_hook->hasOptions()) {
            foreach ($command_hook->getOptions() as $option => $value) {
                if (is_numeric($option)) {
                    $option = $value;
                    $value = null;
                }
                if (!$definition->hasOption($option)) {
                    continue;
                }
                $exec->opt($option, $value);
            }
        }

        // Support symfony command arguments.
        if ($command_hook->hasArguments()) {
            foreach ($command_hook->getArguments() as $arg => $value) {
                if (!isset($value) || !$definition->hasArgument($arg)) {
                    continue;
                }
                $exec->arg($arg, $value);
            }
        }

        return $exec->run();
    }

    /**
     * Get project command.
     *
     * @param CommandHookInterface $command_hook
     *
     * @return CommandBuilder
     */
    protected function getProjectCommand(CommandHookInterface $command_hook)
    {
        $method = $command_hook->getCommand();
        $project = $this->getProjectInstance();

        if (!method_exists($project, $method)) {
            throw new CommandHookRuntimeException(
                sprintf("The %s method doesn't exist on the project.", $method)
            );
        }
        $args = array_merge(
            $command_hook->getOptions(),
            $command_hook->getArguments()
        );

        return call_user_func_array([$project, $method], [$args]);
    }

    /**
     * Get environment engine command.
     *
     * @param CommandHookInterface $command_hook
     *
     * @return CommandBuilder
     */
    protected function getEngineCommand(CommandHookInterface $command_hook)
    {
        $method = $command_hook->getCommand();
        $engine = $this->getEngineInstance();

        if (!method_exists($engine, $method)) {
            throw new CommandHookRuntimeException(
                sprintf("The %s method doesn't exist on the environment engine.", $method)
            );
        }
        $args = array_merge(
            $command_hook->getOptions(),
            $command_hook->getArguments()
        );

        return call_user_func_array([$engine, $method], [$args]);
    }

    /**
     * Get commands by method event type.
     *
     * @param $method
     *   The executing method.
     * @param $event_type
     *   The event type that's being executed.
     *
     * @return array
     *   An array of commands defined in project-x command hooks.
     */
    protected function getCommandsByMethodEventType($method, $event_type)
    {
        $hooks = ProjectX::getProjectConfig()->getCommandHooks();
        if (empty($hooks)) {
            return [];
        }
        $info = $this->getCommandInfo($method);
        list($command, $action) = explode(':', $info->getName());

        if (!isset($hooks[$command][$action][$event_type])) {
            return [];
        }

        return $hooks[$command][$action][$event_type];
    }

    /**
     * Get symfony command info.
     *
     * @param $method
     *   The method name.
     *
     * @return CommandInfo
     *   The symfony command info object.
     */
    protected function getCommandInfo($method)
    {
        return CommandInfo::create(get_called_class(), $method);
    }
}
