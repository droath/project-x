<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\FactoryInterface;
use Droath\ProjectX\ProjectX;

/**
 * Project-X engine type factory.
 */
class EngineTypeFactory implements FactoryInterface
{
    /**
     * Create engine type instance.
     *
     * @return \Droath\ProjectX\Engine\EngineTypeInterface
     */
    public function createInstance()
    {
        $classname = $this->getEngineClass();

        if (!class_exists($classname)) {
            throw new \Exception(
                sprintf('Unable to locate class %s', $classname)
            );
        }

        return new $classname();
    }

    /**
     * Get engine type class based on project configs.
     *
     * @return \Droath\ProjectX\Engine\EngineTypeInterface
     */
    protected function getEngineClass()
    {
        $engine = ProjectX::getProjectConfig()->getEngine();

        if (!isset($engine)) {
            throw new \Exception(
                'Missing project engine definition'
            );
        }

        switch ($engine) {
            case 'docker':
                return '\Droath\ProjectX\Engine\DockerEngineType';
        }
    }
}
