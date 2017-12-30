<?php

namespace Droath\ProjectX\Engine;

/**
 * Interface ServiceDbInterface
 *
 * @package Droath\ProjectX\Engine
 */
interface ServiceDbInterface
{
    /**
     * Database service username.
     *
     * @return string
     */
    public function username();

    /**
     * Database service password.
     *
     * @return string
     */
    public function password();

    /**
     * Database service database.
     *
     * @return string
     */
    public function database();

    /**
     * Database service protocol.
     *
     * @return string
     */
    public function protocol();

}
