<?php

namespace Droath\ProjectX\Engine;

/**
 * Interface DockerServiceInterface
 *
 * @package Droath\ProjectX\Engine
 */
interface ServiceInterface
{
    /**
     * Docker service name.
     *
     * @return string
     */
    public static function name();

    /**
     * Docker service blueprint.
     *
     * @return \Droath\ProjectX\Engine\DockerService
     */
    public function service();
}
