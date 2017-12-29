<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\Config\DockerComposeConfig;
use Droath\ProjectX\Engine\DockerServices\ApacheService;
use Droath\ProjectX\Engine\DockerServices\MariadbService;
use Droath\ProjectX\Engine\DockerServices\MysqlService;
use Droath\ProjectX\Engine\DockerServices\NginxService;
use Droath\ProjectX\Engine\DockerServices\PhpService;
use Droath\ProjectX\Engine\DockerServices\PostgresService;
use Droath\ProjectX\Engine\DockerServices\RedisService;
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
    use dockerSyncTasks;
    use dockerComposerTasks;

    /**
     * Engine install path.
     */
    const INSTALL_ROOT = '/docker';

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
            exit;
        }

        // Startup docker sync if found in project.
        if ($this->hasDockerSync()) {
            $this->taskDockerSyncStart()
                ->run();

            // Set the docker-sync name in the .env file.
            $this->setDockerSyncNameInEnv();
        }

        // Set host IP address in the .env file.
        $this->setHostIPAddressInEnv();

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
     * {@inheritdoc}
     */
    public function rebuild()
    {
        parent::rebuild();

        $this->rebuildDocker();

        return $this;
    }

    /**
     * Setup Docker configurations.
     *
     * The setup process consist of the following:
     *   - Make engine install path.
     *   - Build docker compose file.
     *
     * @return self
     */
    public function setupDocker()
    {
        $this->_mkdir($this->getInstallPath());
        $this->buildDockerCompose();

        return $this;
    }

    /**
     * Rebuild Docker configurations.
     *
     * The setup process consist of the following:
     *   - Remove docker service directory in project install path.
     *   - Build docker compose file based on docker services.
     *   - If docker-sync is used then rebuild the docker compose dev file.
     *
     * @return self
     */
    public function rebuildDocker()
    {
        $this->_remove($this->getInstallPath() . '/services');
        $this->buildDockerCompose();

        if ($this->hasDockerSync()) {
            $this->buildDockerComposeDev();
        }

        return $this;
    }

    /**
     * Build Docker related structure.
     *
     * The setup process consist of the following:
     *   - Copy docker service templates.
     *   - Generate docker-compose.yml file.
     *   - Save docker-composer.yml configuration.
     *
     * @return self
     */
    public function buildDockerCompose()
    {
        $project_root = ProjectX::projectRoot();
        $this
            ->copyDockerServiceFiles()
            ->generateDockerCompose()
            ->save("{$project_root}/docker-compose.yml");

        return $this;
    }

    /**
     * Build Docker related structure.
     *
     * The setup process consist of the following:
     *   - Generate docker-compose-dev.yml file.
     *   - Save docker-composer-dev.yml configuration.
     *
     * @return self
     */
    public function buildDockerComposeDev()
    {
        $project_root = ProjectX::projectRoot();
        $this
            ->generateDockerCompose(true)
            ->save("{$project_root}/docker-compose-dev.yml");

        return $this;
    }

    /**
     * Setup Docker sync configurations.
     *
     * The setup process consist of the following:
     *   - Set docker sync name in environment file.
     *   - Set host IP address in environment file.
     *   - Build docker-compose-dev.yml configurations.
     *   - Copy docker-sync.yml config into project root.
     *
     * @return void
     * @throws \Exception
     */
    public function setupDockerSync()
    {
        $this
            ->setDockerSyncNameInEnv()
            ->setHostIPAddressInEnv()
            ->buildDockerComposeDev()
            ->copyTemplateFilesToProject([
                'docker-sync.yml' => 'docker-sync.yml',
            ]);
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
     * {@inheritdoc}
     */
    public function templateDirectories()
    {
        return array_merge([
            APP_ROOT . '/templates/docker',
            APP_ROOT . '/templates/docker/services'
        ], parent::templateDirectories());
    }

    /**
     * Get required ports based on services.
     *
     * @return array
     *   An array of unique ports required by defined services.
     */
    public function requiredPorts()
    {
        $ports = [];

        foreach ($this->getServiceInstances() as $info) {
            if (!isset($info['instance'])) {
                continue;
            }
            $instance = $info['instance'];

            if ($instance instanceof ServiceInterface) {
                $ports = array_merge($ports, $instance->getHostPorts());
            }
        }
        $ports = array_unique($ports);

        return array_values($ports);
    }

    /**
     * {@inheritdoc}
     */
    protected static function services()
    {
        return [
            'php' => PhpService::class,
            'redis' => RedisService::class,
            'mysql' => MysqlService::class,
            'nginx' => NginxService::class,
            'apache' => ApacheService::class,
            'mariadb' => MariadbService::class,
            'postgres' => PostgresService::class
        ];
    }

    /**
     * Generate docker-compose object.
     *
     * @param bool $dev
     *   Determine if a dev version should be generated.
     * @return \Droath\ProjectX\Config\DockerComposeConfig
     *   The docker compose configuration.
     */
    protected function generateDockerCompose($dev = false)
    {
        $docker_compose = new DockerComposeConfig();
        $docker_compose->setVersion('2');

        foreach ($this->getServices() as $name => $info) {
            if (!isset($info['type'])) {
                continue;
            }
            $type = $info['type'];
            $instance = self::loadService($type);

            if (isset($info['version'])) {
                $instance->setVersion($info['version']);
            }
            $volumes = $dev ? $instance->devVolumes() : $instance->volumes();
            $service = $dev ? $instance->devService() : $instance->getCompleteService();

            if (!empty($volumes)) {
                $docker_compose->setVolumes($volumes);
            }

            if (!$service->isEmpty()) {
                $docker_compose->setService($name, $service);
            }
        }

        return $docker_compose;
    }

    /**
     * Copy docker service template files.
     */
    protected function copyDockerServiceFiles()
    {
        $root = ProjectX::projectRoot() . "/docker/services";
        $project_type = $this->getProjectType();

        foreach ($this->getServices() as $name => $info) {
            if (!isset($info['type'])) {
                continue;
            }
            $type = $info['type'];
            $instance = self::loadService($type);

            if (isset($info['version'])) {
                $instance->setVersion($info['version']);
            }
            foreach ($instance->templateFiles() as $template => $file_info) {
                $paths = [
                    "{$type}/{$project_type}/{$template}",
                    "{$type}/{$template}"
                ];

                foreach ($paths as $path) {
                    $filepath = $this->getTemplateFilePath($path);

                    if (false !== $filepath) {
                        $filesystem = $this->taskFilesystemStack();
                        $destination = "{$root}/{$type}";

                        if (!file_exists($destination)) {
                            $filesystem->mkdir($destination);
                        }
                        $destination = "{$destination}/" . basename($filepath);

                        $overwrite = isset($file_info['overwrite'])
                            ? $file_info['overwrite']
                            : false;

                        $status = $filesystem
                            ->copy($filepath, $destination, $overwrite)
                            ->run();

                        if ($status->wasSuccessful()
                            && !empty($file_info['variables'])) {
                            $file_task = $this
                                ->taskWriteToFile($destination)
                                ->append();

                            foreach ($file_info['variables'] as $name => $value) {
                                $file_task->place($name, $value);
                            }

                            $file_task->run();
                        }

                        break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Run open port status report.
     *
     * @return bool
     *   Return true if warnings have been issued; otherwise false.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function runOpenPortStatusReport()
    {
        $host = '127.0.0.1';
        $status = $this->getPortStatus($host, $this->requiredPorts());
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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
            ->addTask($this->taskDockerSyncStop())
            ->completion($this->taskDockerSyncClean())
            ->run();
    }

    /**
     * Set docker-sync name in environment file; if not already set.
     */
    protected function setDockerSyncNameInEnv()
    {
        $project_root = ProjectX::projectRoot();
        $project_name = ProjectX::getProjectMachineName();
        $sync_name = uniqid("$project_name-", false);

        $this->taskWriteToFile("{$project_root}/.env")
            ->append()
            ->appendUnlessMatches('/SYNC_NAME=\w+/', "SYNC_NAME=$sync_name")
            ->run();

        return $this;
    }

    /**
     * Set host IP address in environment file.
     */
    protected function setHostIPAddressInEnv()
    {
        $host_ip = ProjectX::clientHostIP();
        $project_root = ProjectX::projectRoot();

        $this->taskWriteToFile("$project_root/.env")
            ->append()
            ->regexReplace('/HOST_IP_ADDRESS=.*/', "HOST_IP_ADDRESS={$host_ip}")
            ->appendUnlessMatches('/HOST_IP_ADDRESS=.*/', "\nHOST_IP_ADDRESS={$host_ip}")
            ->run();

        return $this;
    }
}
