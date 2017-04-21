<?php

namespace Droath\ProjectX\Project\Tasks\PHP;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\PhpProjectType;
use Robo\Tasks;

/**
 * Define Drupal specific tasks.
 */
class PhpTasks extends Tasks
{
    /**
     * Setup TravisCi configurations.
     *
     * @return self
     */
    public function phpTravisCi()
    {
        $this->getProjectInstance()
            ->setupTravisCi();

        return $this;
    }

    /**
     * Setup ProboCi configurations.
     *
     * @return self
     */
    public function phpProboCi()
    {
        $this->getProjectInstance()
            ->setupProboCi();

        return $this;
    }

    /**
     * Setup Behat configurations and initialize.
     *
     * @return self
     */
    public function phpBehat()
    {
        $this->getProjectInstance()
            ->setupBehat()
            ->saveComposer()
            ->updateComposer()
            ->initBehat();

        return $this;
    }

    /**
     * Setup PHPunit configurations.
     *
     * @return self
     */
    public function phpPhpUnit()
    {
        $this->getProjectInstance()
            ->setupPhpUnit()
            ->saveComposer()
            ->updateComposer();

        return $this;
    }

    /**
     * Setup PHPcs configurations.
     *
     * @return self
     */
    public function phpPhpCs()
    {
        $this->getProjectInstance()
            ->setupPhpCodeSniffer()
            ->saveComposer()
            ->updateComposer();

        return $this;
    }

   /**
     * Get the project instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
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
