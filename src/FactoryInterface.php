<?php

namespace Droath\ProjectX;

/**
 * Define the factory interface.
 */
interface FactoryInterface
{
    /**
     * Create a class instance.
     *
     * @return object.
     */
    public function createInstance();
}
