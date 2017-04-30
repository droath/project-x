<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\Engine\EngineTypeResolver;
use Droath\ProjectX\FactoryInterface;
use Droath\ProjectX\ProjectX;

/**
 * Project-X engine type factory.
 */
class EngineTypeFactory implements FactoryInterface
{
    /**
     * Engine type resolver.
     *
     * @var \Droath\ProjectX\TaskSubTypeResolver
     */
    protected $resolver;

    /**
     * Engine type factor constructor.
     */
    public function __construct(EngineTypeResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Create engine type instance.
     *
     * @return \Droath\ProjectX\Engine\EngineTypeInterface
     */
    public function createInstance()
    {
        $classname = $this->getEngineClassname();

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
    protected function getEngineClassname()
    {
        return $this->resolver->getClassname(
            ProjectX::getProjectConfig()->getEngine()
        );
    }
}
