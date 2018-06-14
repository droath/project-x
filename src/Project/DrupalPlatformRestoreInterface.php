<?php

namespace Droath\ProjectX\Project;

/**
 * Drupal platform restore interface.
 */
interface DrupalPlatformRestoreInterface
{
    /**
     * Drupal restore options.
     *
     * @return array
     */
    public function drupalRestoreOptions();

    /**
     * Execute custom Drupal restore method.
     *
     * @param $method
     *   The restore method that should be invoked.
     *
     * @return $this
     */
    public function drupalRestore($method);
}
