<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\Database;

/**
 * Define Project-X project task commands.
 */
class ProjectTasks extends TaskBase
{
    /**
     * Setup a fresh new project.
     */
    public function projectSetupNew()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->projectInstance()->setupNewProject();
        $this->executeCommandHook(__FUNCTION__, 'after');
    }

    /**
     * Setup an existing project.
     *
     * @param array $opts
     *
     * @option string $db-name Set the database name.
     * @option string $db-user Set the database user.
     * @option string $db-pass Set the database password.
     * @option string $db-host Set the database host.
     * @option string $db-port Set the database port.
     * @option string $db-protocol Set the database protocol.
     * @option bool $no-engine Don't start local development engine.
     * @option bool $no-browser Don't launch a browser window after setup is complete.
     * @option string $restore-method Set the database restore method: site-config, or database-import.
     * @option bool $localhost Install database using localhost.
     */
    public function projectSetupExisting($opts = [
        'db-name' => null,
        'db-user' => null,
        'db-pass' => null,
        'db-host' => null,
        'db-port' => null,
        'db-protocol' => null,
        'no-engine' => false,
        'no-browser' => false,
        'restore-method' => null,
        'localhost' => false,
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $database = Database::createFromArray([
            'port' => $opts['db-port'],
            'user' => $opts['db-user'],
            'password' => $opts['db-pass'],
            'database' => $opts['db-name'],
            'hostname' => $opts['db-host'],
            'protocol' => $opts['db-protocol']
        ]);
        $this->projectInstance()
            ->setDatabaseOverride($database)
            ->setupExistingProject(
                $opts['no-engine'],
                $opts['restore-method'],
                $opts['no-browser'],
                $opts['localhost']
            );
        $this->executeCommandHook(__FUNCTION__, 'after');
    }
}
