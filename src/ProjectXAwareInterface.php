<?php

namespace Droath\ProjectX;

/**
 * Define the Project-X aware interface.
 */
interface ProjectXAwareInterface
{
    /**
     * Set Project-X configuration path.
     *
     * @param string $path
     *   The path to the project-x file.
     */
    public function setProjectXConfigPath($path);
}
