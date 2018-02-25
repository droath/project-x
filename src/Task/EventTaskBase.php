<?php

namespace Droath\ProjectX\Task;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Droath\ProjectX\CommandHook;
use Droath\ProjectX\Exception\CommandHookRuntimeException;
use Droath\ProjectX\ProjectX;
use Robo\Contract\TaskInterface;
use Robo\Tasks;

/**
 * Event task base object.
 */
abstract class EventTaskBase extends Tasks
{

    /**
     * Execute command hook.
     *
     * @param $method
     *   The task method command.
     * @param $event_type
     *   The event type on which to execute.
     *
     * @return $this
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

            if (!$command instanceof CommandHook) {
                continue;
            }
            $execute = $this->getCommandHookExecute($command, $method);

            if (!$execute || !$execute instanceof TaskInterface) {
                continue;
            }
            $execute->run();
        }

        return $this;
    }

    /**
     * Get command hook execute task.
     *
     * @param CommandHook $command_hook
     *   The command hook object.
     * @param $parent_method
     *   The parent method that's executing the command hook.
     *
     * @return bool|\Robo\Task\Base\Exec|\Robo\Task\Base\SymfonyCommand
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getCommandHookExecute(CommandHook $command_hook, $parent_method)
    {
        $hook_type = $command_hook->getType();

        if (!isset($hook_type)) {
            return false;
        }
        $command = $command_hook->getCommand();

        if (!isset($command)) {
            return false;
        }
        switch ($hook_type) {
            case 'symfony':
                try {
                    $info = $this->getCommandInfo($parent_method);
                    $container = ProjectX::getContainer();
                    $application = $container->get('application');

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
                break;
            case 'raw':
            default:
                $exec = $this->taskExec($command);
        }

        return $exec;
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
