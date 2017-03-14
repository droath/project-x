<?php

namespace Droath\ProjectX\Project;

/**
 * Define Project-X type interface.
 */
interface ProjectTypeInterface
{
    /**
     * Specify project build process.
     */
    public function build();

    /**
     * Specify project install process.
     */
    public function install();
}
