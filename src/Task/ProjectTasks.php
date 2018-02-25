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
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this
            ->projectBuild()
            ->projectInstall();
        $this->executeCommandHook(__FUNCTION__, 'after');
    }

    /**
     * Run project build process.
     */
    public function projectBuild()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->projectInstance()->build();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Run project install process.
     */
    public function projectInstall()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->projectInstance()->install();
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }
}
