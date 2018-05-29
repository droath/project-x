<?php

namespace Droath\ProjectX\Project;

/**
 * Define Project-X type interface.
 */
interface ProjectTypeInterface
{
    /**
     * Setup a fresh new project.
     *
     * @return mixed
     */
    public function setupNewProject();

    /**
     * Setup a rushy old project.
     *
     * @return mixed
     */
    public function setupExistingProject();
}
