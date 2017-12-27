<?php

namespace Droath\ProjectX\Engine\DockerServices;

/**
 * Class MariadbService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class MariadbService extends MysqlService
{
    const DEFAULT_VERSION = 5.5;

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'mariadb';
    }

    /**
     * {@inheritdoc}
     */
    protected function dbType()
    {
        return 'mariadb';
    }
}
