<?php

namespace Droath\ProjectX\Project\Tasks\PHP;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\PhpProjectType;
use Droath\ProjectX\Task\EventTaskBase;

/**
 * Define Drupal specific tasks.
 */
class PhpTasks extends EventTaskBase
{
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
