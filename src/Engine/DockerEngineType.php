<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubTypeInterface;
use Droath\RoboDockerCompose\Task\loadTasks as dockerComposerTasks;
use Droath\RoboDockerSync\Task\loadTasks as dockerSyncTasks;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Define docker engine type.
 */
class DockerEngineType extends EngineType implements TaskSubTypeInterface
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
    public static function getLabel()
    {
        return 'Docker';
    }

    /**
     * {@inheritdoc}.
     */
    public static function getTypeId()
    {
        return 'docker';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        parent::up();

        // Run open port status report. Display a confirmation message if
        // warning(s) have been issued. User will need to confirm if they want
        // to continue or not.
        $status = $this->runOpenPortStatusReport();

        if (!$status) {
            return false;
        }

        // Startup docker sync if found in project.
        if ($this->hasDockerSync()) {
            $this->taskDockerSyncDaemonStart()
                ->run();
        }

        // Write host IP address to .env file for xdebug
        $this->updateHostIPAddress();

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
    public function start()
    {
        parent::start();

        $this->taskDockerComposeStart()
            ->run();
    }

    /**
     * {@inheritdoc}
     */
    public function restart()
    {
        parent::restart();

        $this->taskDockerComposeRestart()
            ->run();
    }

    /**
     * {@inheritdoc}
     */
    public function suspend()
    {
        parent::suspend();

        $this->taskDockerComposePause()
            ->run();
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
        $project_root = ProjectX::projectRoot();

        $this->taskFilesystemStack()
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
        $project_root = ProjectX::projectRoot();

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

        $project_name = ProjectX::getProjectMachineName();

        $sync_name = uniqid("$project_name-", false);

        // Append the sync name with to the project .env file.
        $this->taskWriteToFile("{$project_root}/.env")
            ->append()
            ->appendUnlessMatches('/SYNC_NAME=\w+/', "SYNC_NAME=$sync_name")
            ->run();

        $this->updateHostIPAddress();
    }

    /**
     * Has docker sync configuration.
     *
     * @return bool
     *   Return true if a docker-sync config is found; otherwise false.
     */
    public function hasDockerSync()
    {
        $project_root = ProjectX::projectRoot();

        return file_exists("{$project_root}/docker-sync.yml");
    }

    /**
     * Run open port status report.
     *
     * @return bool
     *   Return true if warnings have been issued; otherwise false.
     */
    protected function runOpenPortStatusReport()
    {
        $host = '127.0.0.1';
        $ports = ['80', '3306'];
        $status = $this->getPortStatus($host, $ports);
        $has_warning = isset($status['state']['warning'])
            && $status['state']['warning'] !== 0
                ? true
                : false;

        if ($has_warning) {
            $this->io()
                ->caution(
                    "Another process on your system is using the same port(s).\n" .
                    'Please review the report below for more details.'
                );
        }
        $table = new Table($this->getOutput());
        $table
            ->setHeaders(['Port', 'Status'])
            ->setRows($this->buildPortStatusRows($status));

        $table->render();

        if ($has_warning) {
            $confirm = $this->askConfirmQuestion('Do you want to continue?');

            if (!$confirm) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build port status rows.
     *
     * @param array $status
     *   An array of the status data.
     *
     * @return array
     *   An array of the rows based on the status.
     */
    protected function buildPortStatusRows(array $status)
    {
        $rows = [];

        foreach ($status['ports'] as $port => $value) {
            $row = [
                $port,
                $value['status'],
            ];

            $rows[] = $row;
        }
        $warnings = $status['state']['warning'];

        $rows[] = new TableSeparator();
        $rows[] = [new TableCell(
            sprintf('There is %d warning(s) on %s!', $warnings, $status['host']),
            ['colspan' => 2]
        )];

        return $rows;
    }

    /**
     * Get host port status.
     *
     * @param string $host
     *   The hostname to check.
     * @param array $ports
     *   An array of ports to check.
     *
     * @return array
     *   An array of port status data.
     */
    protected function getPortStatus($host, array $ports)
    {
        $status = [];
        $hostchecker = $this->getContainer()
            ->get('projectXHostChecker');

        $warning_count = 0;

        foreach ($ports as $port) {
            $response = $hostchecker
                ->setHost($host)
                ->setPort($port)
                ->isPortOpen();

            $status['ports'][$port] = [
                'status' => $response ? '❌' : '✅',
                'is_open' => (int) $response,
            ];

            if ($response) {
                ++$warning_count;
            }
        }
        $status['host'] = $host;
        $status['state']['warning'] = $warning_count;

        return $status;
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

        $root = ProjectX::projectRoot();
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

    /**
     * Write host IP address to file. Append if it doesn't exsit
     * or update the value if it does exist.
     */
    protected function updateHostIPAddress()
    {
        $host_ip = ProjectX::clientHostIP();
        $project_root = ProjectX::projectRoot();

        $this->taskWriteToFile("$project_root/.env")
            ->append()
            ->regexReplace('/HOST_IP_ADDRESS=.*/', "HOST_IP_ADDRESS={$host_ip}")
            ->appendUnlessMatches('/HOST_IP_ADDRESS=.*/', "\nHOST_IP_ADDRESS={$host_ip}")
            ->run();
    }
}
