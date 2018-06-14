<?php

namespace Droath\ProjectX;

/**
 * Define project trait.
 */
trait ProjectTrait
{
    /**
     * Project type instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    protected function getProjectInstance()
    {
        return ProjectX::getProjectType();
    }

    /**
     * Project type instance.
     *
     * @deprecated Use getProjectInstance()
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    protected function projectInstance()
    {
        return $this->getProjectInstance();
    }
}
