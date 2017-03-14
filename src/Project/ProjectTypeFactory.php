<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\FactoryInterface;
use Droath\ProjectX\ProjectXAwareTrait;

/**
 * Project-X project type factory.
 */
class ProjectTypeFactory implements FactoryInterface
{
    use ProjectXAwareTrait;

    /**
     * Create project type instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    public function createInstance()
    {
        $classname = $this->getProjectClass();

        if (!class_exists($classname)) {
            throw new \Exception(
                sprintf('Unable to locate class %s', $classname)
            );
        }

        return new $classname();
    }

    /**
     * Get project type class based on project configs.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    protected function getProjectClass()
    {
        $config = $this->getProjectXConfig();

        if (!isset($config['type'])) {
            throw new \Exception(
                'Missing project type definition'
            );
        }

        switch ($config['type']) {
            case 'drupal':
                return '\Droath\ProjectX\Project\DrupalProjectType';
        }
    }
}
