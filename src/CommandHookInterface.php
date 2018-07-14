<?php

namespace Droath\ProjectX;

/**
 * Define command hook interface
 */
interface CommandHookInterface
{
    /**
     * Get command hook type.
     *
     * @return string
     */
    public function getType();

    /**
     * Get command hook command.
     *
     * @return string
     */
    public function getCommand();

    /**
     * Get command hook options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Get command hook arguments.
     *
     * @return array
     */
    public function getArguments();
}
