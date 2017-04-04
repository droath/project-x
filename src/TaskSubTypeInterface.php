<?php

namespace Droath\ProjectX;

/**
 * Define task subtype interface.
 */
interface TaskSubTypeInterface
{
    /**
     * Define a human readable label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Define a type identifier.
     *
     * @return string
     */
    public function getTypeId();
}
