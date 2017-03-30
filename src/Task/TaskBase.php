<?php

namespace Droath\ProjectX\Task;

use Robo\Tasks;

/**
 * Define Project-X task base.
 */
abstract class TaskBase extends Tasks
{
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
