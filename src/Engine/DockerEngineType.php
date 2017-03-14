<?php

namespace Droath\ProjectX\Engine;

use Droath\RoboDockerCompose\Task\loadTasks;

/**
 * Define docker engine type.
 */
class DockerEngineType extends EngineType
{
    use loadTasks;

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        parent::up();

        // Startup docker using Robo docker compose task.
        $this->taskDockerComposeUp()
            ->detachedMode()
            ->removeOrphans()
            ->run();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        parent::down();

        // Shutdown docker using Robo docker compose task.
        $this->taskDockerComposeDown()
            ->run();
    }

    /**
     * {@inheritdoc}.
     */
    public function getTypeId()
    {
        return 'docker';
    }
}
