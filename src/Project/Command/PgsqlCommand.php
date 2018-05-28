<?php

namespace Droath\ProjectX\Project\Command;

use Droath\ProjectX\CommandBuilder;

class PgsqlCommand extends CommandBuilder
{
    protected $executable = 'psql';

    public function host($host)
    {
        $this->setOption('host', $host, '=');

        return $this;
    }

    public function username($username)
    {
        $this->setOption('username', $username, '=');

        return $this;
    }

    public function password($password)
    {
        $this->setEnvVariable('PGPASSWORD', $password);

        return $this;
    }

    public function database($database)
    {
        $this->setOption('dbname', $database, '=');

        return $this;
    }
}
