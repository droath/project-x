<?php

namespace Droath\ProjectX;

/**
 * Define configuration interface.
 */
interface ConfigInterface
{
    /**
     * Get Project-X config project type.
     *
     * @return string
     */
    public function getType();

    /**
     * Get Project-X config project options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Has Project-X configuration.
     *
     * @return bool
     */
    public function hasConfig();

    /**
     * Get Project-X parse output.
     *
     * @return array
     */
    public function getConfig();

    /**
     * Has Project-X local configuration.
     *
     * @return bool
     */
    public function hasConfigLocal();

    /**
     * Get Project-X local configuration.
     *
     * @return array
     */
    public function getConfigLocal();
}
