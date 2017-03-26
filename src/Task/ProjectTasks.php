<?php

namespace Droath\ProjectX\Task;

/**
 * Define Project-X project task commands.
 */
class ProjectTasks extends TaskBase
{
    /**
     * Run all project processes.
     */
    public function projectInit()
    {
        $this
            ->projectDependsInstall()
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

    /**
     * Run project dependencies installation.
     */
    public function projectDependsInstall()
    {
        // @todo: install local OS dependencies using brew, ruby gems via ansible.

        return $this;
    }
}
