<?php

namespace Droath\ProjectX\Project\Tasks\PHP;

use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\PhpProjectType;
use Droath\ProjectX\Task\EventTaskBase;
use Droath\ProjectX\TaskResultTrait;
use Droath\RoboDockerCompose\Task\loadTasks as dockerComposeTasks;

/**
 * Define Drupal specific tasks.
 */
class PhpTasks extends EventTaskBase
{
    use TaskResultTrait;
    use dockerComposeTasks;

    /**
     * Setup TravisCi configurations.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function phpTravisCi()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->getProjectInstance()
            ->setupTravisCi();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Setup ProboCi configurations.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function phpProboCi()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->getProjectInstance()
            ->setupProboCi();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Setup Behat configurations and initialize.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function phpBehat()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->getProjectInstance()
            ->setupBehat()
            ->saveComposer()
            ->updateComposer()
            ->initBehat();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Setup PHPunit configurations.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function phpPhpUnit()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->getProjectInstance()
            ->setupPhpUnit()
            ->saveComposer()
            ->updateComposer();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Setup PHPcs configurations.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function phpPhpCs()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->getProjectInstance()
            ->setupPhpCodeSniffer()
            ->saveComposer()
            ->updateComposer();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Php composer command.
     *
     * @aliases composer
     *
     * @param array $composer_command The composer command to execute.
     * @param array $opts
     * @option string $remote-binary-path The path to the Drush binary.
     * @option string $remote-working-dir The remote Drupal root directory.
     *
     * @return $this
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function phpComposer(array $composer_command, $opts = [
        'remote-binary-path' => 'composer',
        'remote-working-dir' => '/var/www/html',
    ])
    {
        /** @var DrupalProjectType $project */
        $instance = $this->getProjectInstance();

        $engine = ProjectX::getEngineType();
        $binary = $opts['remote-binary-path'];
        $command_str = escapeshellcmd(implode(' ', $composer_command));
        $working_dir = escapeshellarg($opts['remote-working-dir']);

        $command = $this->taskExec("{$binary} --working-dir={$working_dir} {$command_str}");

        if ($engine instanceof DockerEngineType) {
            $container = $instance->getPhpServiceName('php');
            $result = $this->taskDockerComposeExecute()
                ->setContainer($container)
                ->exec($command)
                ->run();
        } else {
            $result = $command->run();
        }
        $this->validateTaskResult($result);

        return $this;
    }

    /**
     * Import PHP project database.
     *
     * @param array $opts
     * @option string $service The database service name.
     * @option string $file_path The path to the database exported file.
     * @option bool $localhost Run the database import command from localhost.
     *
     * @throws \Exception
     */
    public function phpImportDatabase($opts = [
        'service' => null,
        'file_path' => null,
        'localhost' => false,
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        /** @var PhpProjectType $instance */
        $instance = $this->getProjectInstance();
        $instance->importDatabaseToService(
            $opts['service'],
            $opts['file_path'],
            $opts['localhost']
        );
        $this->executeCommandHook(__FUNCTION__, 'after');
    }

    /**
     * Get the project instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getProjectInstance()
    {
        $project = ProjectX::getProjectType();

        if (!$project instanceof PhpProjectType) {
            throw new \Exception(
                'These tasks can only be ran for PHP based projects.'
            );
        }
        $project->setBuilder($this->getBuilder());

        return $project;
    }
}
