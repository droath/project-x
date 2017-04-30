<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\FactoryInterface;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\ProjectTypeResolver;

/**
 * Project-X project type factory.
 */
class ProjectTypeFactory implements FactoryInterface
{
    /**
     * Project type resolver.
     *
     * @var \Droath\ProjectX\TaskSubTypeResolver
     */
    protected $resolver;

    /**
     * Project type factor constructor.
     */
    public function __construct(ProjectTypeResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Create project type instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    public function createInstance()
    {
        $classname = $this->getProjectClassname();

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
    protected function getProjectClassname()
    {
        return $this->resolver->getClassname(
            ProjectX::getProjectConfig()->getType()
        );
    }
}
