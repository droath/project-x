<?php

namespace Droath\ProjectX\Project\Command;

use Droath\ProjectX\CommandBuilder;

/**
 * MySQL command.
 */
class MysqlCommand extends CommandBuilder
{
    protected $executable = 'mysql';

    /**
     * Database host.
     *
     * @param $host
     *
     * @return $this
     */
    public function host($host)
    {
        $this->setOption('host', $host, '=');

        return $this;
    }

    /**
     * Database username.
     *
     * @param $username
     *
     * @return $this
     */
    public function username($username)
    {
        $this->setOption('user', $username, '=');

        return $this;
    }

    /**
     * Database password.
     *
     * @param $password
     *
     * @return $this
     */
    public function password($password)
    {
        $this->setOption('password', $password, '=');

        return $this;
    }

    /**
     * Database name.
     *
     * @param $database
     *
     * @return $this
     */
    public function database($database)
    {
        $this->setOption('database', $database, '=');

        return $this;
    }
}
