<?php

namespace Droath\ProjectX\Project;

/**
 * Define Project-X type interface.
 */
interface ProjectTypeInterface
{
    /**
     * Specify project setup process.
     */
    public function setup();

    /**
     * Specify project install process.
     */
    public function install();
}
