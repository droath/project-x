<?php

namespace Droath\ProjectX\Engine;

/**
 * Interface for environment engine service.
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
     * Docker service group.
     *
     * @return string
     */
    public static function group();

    /**
     * Docker service blueprint.
     *
     * @return \Droath\ProjectX\Engine\DockerService
     */
    public function service();
}
