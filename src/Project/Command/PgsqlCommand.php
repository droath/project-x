<?php

namespace Droath\ProjectX\Project\Command;

use Droath\ProjectX\CommandBuilder;

/**
 * Postgres SQL command.
 */
class PgsqlCommand extends CommandBuilder
{
    protected $executable = 'psql';

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
        $this->setOption('username', $username, '=');

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
        $this->setEnvVariable('PGPASSWORD', $password);

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
        $this->setOption('dbname', $database, '=');

        return $this;
    }
}
