<?php

namespace Droath\ProjectX\Engine\DockerServices;

/**
 * Interface DockerServiceInterface
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
interface DockerServiceInterface
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
