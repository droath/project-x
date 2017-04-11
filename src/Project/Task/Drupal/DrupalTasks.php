<?php

namespace Droath\ProjectX\Task\Drupal;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\DrupalProjectType;
use Robo\Tasks;

/**
 * Define Drupal specific tasks.
 */
class DrupalTasks extends Tasks
{
    /**
     * Install Drupal on the current environment.
     */
    public function drupalInstall()
    {
        $this->getProjectInstance()
            ->setupDrupalInstall();
    }

    /**
     * Setup local environment for already built projects.
     */
    public function drupalLocalSetup()
    {
        $this->getProjectInstance()
            ->projectEngineUp()
            ->setupDrupalLocalSettings()
            ->setupDrupalInstall()
            ->projectLaunchBrowser();
    }

    /**
     * Get the project instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    protected function getProjectInstance()
    {
        $project = ProjectX::getProjectType();

        if (!$project instanceof DrupalProjectType) {
            throw new \Exception(
                'These tasks can only be ran for Drupal projects.'
            );
        }
        $project->setBuilder($this->getBuilder());

        return $project;
    }
}
