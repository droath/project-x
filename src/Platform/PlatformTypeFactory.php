<?php

namespace Droath\ProjectX\Platform;

use Droath\ProjectX\FactoryInterface;
use Droath\ProjectX\Project\ProjectTypeResolver;
use Droath\ProjectX\ProjectX;

/**
 * Define the platform type factory.
 */
class PlatformTypeFactory implements FactoryInterface
{
    /**
     * Platform type resolver.
     *
     * @var ProjectTypeResolver
     */
    protected $resolver;

    /**
     * Platform type factor constructor.
     *
     * @param \Droath\ProjectX\Platform\PlatformTypeResolver $resolver
     */
    public function __construct(PlatformTypeResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Create a class instance.
     *
     * @return object.
     * @throws \Exception
     */
    public function createInstance()
    {
        $classname = $this->getPlatformClassname();

        if (!class_exists($classname)) {
            throw new \Exception(
                sprintf('Unable to locate class %s', $classname)
            );
        }

        return new $classname();
    }

    /**
     * Get platform classname.
     *
     * @return mixed
     */
    protected function getPlatformClassname()
    {
        return $this->resolver->getClassname(
            ProjectX::getProjectConfig()->getPlatform()
        );
    }
}
