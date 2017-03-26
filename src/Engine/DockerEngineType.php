<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\ProjectX;
use Droath\RoboDockerCompose\Task\loadTasks as dockerComposerTasks;
use Droath\RoboDockerSync\Task\loadTasks as dockerSyncTasks;

/**
 * Define docker engine type.
 */
class DockerEngineType extends EngineType
{
    /**
     * Engine install path.
     */
    const INSTALL_ROOT = '/docker';

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

        // Shutdown docker sync if config is found.
        if ($this->hasDockerSync()) {
            $this->runDockerSyncDownCollection();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        parent::install();
        $this->setupDocker();

        if ($this->useDockerSync()) {
            $this->setupDockerSync();
        }
    }

    /**
     * Setup Docker configurations.
     *
     * The setup process consist of the following:
     *   - Make engine install path.
     *   - Move docker services into project install path.
     *   - Copy docker-composer.yml config into project root.
     *
     * @return self
     */
    public function setupDocker()
    {
        $project_root = $this->getProjectXRootPath();

        $this->taskfilesystemStack()
            ->mkdir($this->getInstallPath())
            ->mirror($this->getTemplateFilePath('docker/services'), "{$this->getInstallPath()}")
            ->copy($this->getTemplateFilePath('docker/docker-compose.yml'), "{$project_root}/docker-compose.yml")
            ->run();

        return $this;
    }

    /**
     * Setup Docker sync configurations.
     *
     * The setup process consist of the following:
     *   - Copy docker-sync.yml config into project root.
     *   - Copy docker-composer-dev.yml config into project root.
     *   - Replace hosts IP address placeholder in docker-compose.dev.yml.
     *   - Write the sync name to the project .env file.
     *
     * @return self
     */
    public function setupDockerSync()
    {
        $project_root = $this->getProjectXRootPath();

        $this->copyTemplateFilesToProject([
            'docker/docker-sync.yml' => 'docker-sync.yml',
            'docker/docker-compose-dev.yml' => 'docker-compose-dev.yml',
        ]);

        // Update the docker compose development configuration to replace
        // the placeholder variables with the valid host IP.
        $this->taskWriteToFile("$project_root/docker-compose-dev.yml")
            ->append()
            ->place('HOST_IP_ADDRESS', ProjectX::clientHostIP())
            ->run();

        $project_name = $this->getApplication()
            ->getProjectMachineName();

        $sync_name = uniqid("$project_name-", false);

        // Append the sync name with to the project .env file.
        $this->taskWriteToFile("{$project_root}/.env")
            ->append()
            ->appendUnlessMatches('/SYNC_NAME=\w+/', "SYNC_NAME=$sync_name")
            ->run();
    }

    /**
     * Has docker sync configuration.
     *
     * @return bool
     *   Return true if a docker-sync config is found; otherwise false.
     */
    public function hasDockerSync()
    {
        $root = $this->getProjectXRootPath();

        return file_exists("{$root}/docker-sync.yml");
    }

    /**
     * Ask user to confirm it should install docker sync.
     *
     * @return bool
     *   Return true if docker sync should be install; otherwise false.
     */
    protected function useDockerSync()
    {
        return $this->askConfirmQuestion('Use Docker Sync?', true);
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
        $path = "{$root}/docker-compose-dev.yml";

        if ($this->hasDockerSync() && file_exists($path)) {
            $files[] = $path;
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
