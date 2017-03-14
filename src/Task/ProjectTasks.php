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
            ->projectEngineInstall()
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

    /**
     * Run project engine installation.
     */
    public function projectEngineInstall()
    {
        $engine = $this->engineInstance();
        $project = $this->projectInstance();

        if ($engine->getTypeId() === 'docker'
            && $project->hasDockerSupport()) {
            $project->dockerInstall();
        } else {
            $engine->install();
        }

        return $this;
    }

    /**
     * Startup project engine.
     */
    public function projectUp()
    {
        $this->engineInstance()->up();

        return $this;
    }

    /**
     * Shutdown project engine.
     */
    public function projectDown()
    {
        $this->engineInstance()->down();

        return $this;
    }

    /**
     * Engine type instance.
     *
     * @return \Droath\ProjectX\Engine\EngineTypeInterface
     */
    protected function engineInstance()
    {
        return $this->container
            ->get('projectXEngine')
            ->setBuilder($this->getBuilder());
    }

    /**
     * Project type instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    protected function projectInstance()
    {
        return $this->container
            ->get('projectXProject')
            ->setBuilder($this->getBuilder());
    }
}
