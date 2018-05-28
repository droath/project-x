<?php

namespace Droath\ProjectX\Project\Command;

use Droath\ProjectX\CommandBuilder;

class MysqlCommand extends CommandBuilder
{
    protected $executable = 'mysql';


    public function host($host)
    {
        $this->setOption('host', $host);

        return $this;
    }

    public function username($username)
    {
        $this->setOption('user', $username);

        return $this;
    }

    public function password($password)
    {
        $this->setOption('password', $password);

        return $this;
    }

    public function database($database)
    {
        $this->setOption('database', $database);

        return $this;
    }

    public function import($filename)
    {
        $this->command("< {$filename}");

        return $this;
    }
}
