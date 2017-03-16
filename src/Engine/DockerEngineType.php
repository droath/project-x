<?php

namespace Droath\ProjectX\Engine;

use Droath\RoboDockerSync\Task\loadTasks as dockerSyncTasks;
use Droath\RoboDockerCompose\Task\loadTasks as dockerComposerTasks;

/**
 * Define docker engine type.
 */
class DockerEngineType extends EngineType
{
    use dockerSyncTasks;
    use dockerComposerTasks;

    /**
     * {@inheritdoc}.
     */
    public function getTypeId()
    {
        return 'docker';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        parent::up();

        // Startup docker sync if found in project.
        if ($this->hasDockerSync()) {
            $this->taskDockerSyncDaemonStart()
                ->run();
        }

        // Startup docker compose.
        $this->taskDockerComposeUp()
            ->files($this->getDockerComposeFiles())
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

        // Shutdown docker compose.
        $this->taskDockerComposeDown()
            ->run();

        // Shutdown docker sync if found in project.
        if ($this->hasDockerSync()) {
            $this->runDockerSyncDownCollection();
        }
    }

    /**
     * Ask user to confirm it should install docker sync.
     *
     * @return bool
     *   Return true if docker sync should be install; otherwise false.
     */
    public function useDockerSync()
    {
        return $this->confirm('Use Docker Sync?');
    }

    /**
     * Has docker sync configuration.
     *
     * @return bool
     *   Return true if a docker-sync config is found; otherwise false.
     */
    protected function hasDockerSync()
    {
        $root = $this->getProjectXRootPath();

        return file_exists("{$root}/docker-sync.yml");
    }

    /**
     * Get docker compose files to load.
     *
     * @return array
     *   An array of docker compose files.
     */
    protected function getDockerComposeFiles()
    {
        $files = [
            'docker-compose.yml',
        ];

        $root = $this->getProjectXRootPath();
        $dev_compose = "{$root}/docker-compose-dev.yml";

        if (file_exists($dev_compose)
            && $this->hasDockerSync()) {
            $files[] = $dev_compose;
        }

        return $files;
    }

    /**
     * Shutdown docker-sync collection.
     */
    protected function runDockerSyncDownCollection()
    {
        $this->collectionBuilder()
            ->addTask($this->taskDockerSyncDaemonStop())
            ->completion($this->taskDockerSyncDaemonClean())
            ->run();
    }
}
