<?php

namespace Droath\ProjectX\Task;

/**
 * Define Project-X project task commands.
 */
class ProjectTasks extends TaskBase
{
    /**
     * Run all project setup processes (build, install, etc).
     */
    public function projectSetup()
    {
        $this
            ->projectBuild()
            ->projectInstall();
    }

    /**
     * Run project build process.
     */
    public function projectBuild()
    {
        $this->projectInstance()->build();

        return $this;
    }

    /**
     * Run project install process.
     */
    public function projectInstall()
    {
        $this->projectInstance()->install();

        return $this;
    }
}
