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
use Droath\ProjectX\Exception\EngineRuntimeException;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskCommonTrait;
use Droath\ProjectX\TaskSubTypeInterface;
use Droath\RoboDockerCompose\Task\loadTasks as dockerComposerTasks;
use Droath\RoboDockerCompose\Task\Ps;
use Droath\RoboDockerSync\Task\loadTasks as dockerSyncTasks;
use Robo\ResultData;
use Robo\Task\Docker\Exec;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Define docker engine type.
 */
class DockerEngineType extends EngineType implements TaskSubTypeInterface
{
    use TaskCommonTrait;
    use dockerSyncTasks;
    use dockerComposerTasks;

    /**
     * Engine install path.
     */
    const INSTALL_ROOT = '/docker';

    /**
     * Define the docker version.
     */
    const DOCKER_VERSION = '3';

    /**
     * Define the traefik required ports.
     */
    const TRAEFIK_PORTS = ['80', '8080'];

    /**
     * Define the traefik version number.
     */
    const TRAEFIK_VERSION = '1.6-alpine';

    /**
     * Define the traefik network name.
     */
    const TRAEFIK_NETWORK = 'project-x-proxy';

    /**
     * Define the traefik container name.
     */
    const TRAEFIK_CONTAINER_NAME = 'project-x-traefik';

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

        $continue = $this->showRequiredPortsTable(
            $this->getPortsToScan()
        );

        if (!$continue) {
            throw new EngineRuntimeException(
                'Environment engine was aborted due to conflicting ports.'
            );
        }

        // Start traefik to route docker containers in the networks.
        $this->startTraefik();

        // Update the latest docker compose images.
        $this->updateDockerComposeImages();

        // Build the docker compose services based on changes in the Dockerfile.
        $this->buildDockerComposeServices();

        // Startup docker sync if found in project.
        if ($this->hasDockerSync() && !$this->isDockerSyncRunning()) {
            // Set the docker-sync name in the .env file.
            $this->setDockerSyncNameInEnv();

            // Start and determine if docker-sync task result are valid.
            $this->validateTaskResult(
                $this->taskDockerSyncStart()->run()
            );
        }

        // Set host IP address in the .env file.
        $this->setHostIPAddressInEnv();

        // Startup docker compose.
        $result = $this->taskDockerComposeUp()
            ->files($this->getDockerComposeFiles())
            ->detachedMode()
            ->removeOrphans()
            ->run();

        // Determine if docker compose result are valid.
        $this->validateTaskResult($result);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down($include_network = false)
    {
        parent::down($include_network);

        // Shutdown docker compose.
        $this->taskDockerComposeDown()
            ->run();

        // Shutdown docker sync if config is found.
        if ($this->hasDockerSync()) {
            $this->runDockerSyncDownCollection();
        }

        // Shutdown the docker traefik container.
        if ($this->hasTraefik() && $include_network) {
            $this->stopTraefik();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        parent::start();

        $this->taskDockerComposeStart()
            ->run();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function restart()
    {
        parent::restart();

        $this->taskDockerComposeRestart()
            ->run();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function suspend()
    {
        parent::suspend();

        $this->taskDockerComposePause()
            ->run();

        return $this;
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

        return $this;
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
     * {@inheritdoc}
     */
    public function reboot($include_network = false)
    {
        parent::reboot($include_network);

        $this->down($include_network)->up();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ssh($service = null)
    {
        $container_id = $this->askForServiceContainerId($service);

        $this->runCommandInContainer(
            $container_id,
            'bash',
            ['-t' => null],
            false,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logs($show = 'all', $follow = false, $service = null)
    {
        if (!isset($service)) {
            $service = $this->askForServiceName();
        }
        $docker_logs = $this->taskDockerComposeLogs()
            ->setService($service)
            ->tail($show);

        if ($follow) {
            $docker_logs->follow();
        }

        $docker_logs->run();
    }

    /**
     * {@inheritdoc}
     */
    public function exec($command, $service = null)
    {
        $result = $this->execRaw($command, $service);

        if ($result->getExitCode() !== ResultData::EXITCODE_OK) {
            return false;
        }

        return $result->getMessage();
    }

    /**
     * Execute raw command.
     *
     * @param $command
     *   The command to run.
     * @param null $service
     *   The service container to execute within.
     * @param array $options
     *   An array of command options.
     * @param bool $quiet
     *   Determine if the command should print output.
     *
     * @return ResultData
     */
    public function execRaw($command, $service = null, $options = [], $quiet = false)
    {
        $container_id = $this->askForServiceContainerId($service);

        return $this->runCommandInContainer($container_id, $command, $options, $quiet);
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
     * Using the traefik network proxy.
     *
     * @return bool
     */
    public function hasTraefik()
    {
        $network = ProjectX::getProjectConfig()->getNetwork();

        return isset($network['proxy']) && $network['proxy']
            ? $network['proxy']
            : false;
    }

    /**
     * Determine if the traefik network proxy is running.
     *
     * @return bool
     */
    public function isTraefikRunning()
    {
        return $this->hasDockerContainer(self::TRAEFIK_CONTAINER_NAME)
            && $this->isContainerRunning(self::TRAEFIK_CONTAINER_NAME);
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
     * Determine if docker sync running.
     *
     * @return bool
     */
    public function isDockerSyncRunning()
    {
        $container = $this->getDockerSyncContainer();

        return $this->hasDockerContainer($container)
            && $this->isContainerRunning($container);
    }

    /**
     * Get docker sync unique name.
     *
     * @return string|null
     *   The docker sync name set in the environment file.
     */
    public function getDockerSyncName()
    {
        return getenv('SYNC_NAME') ?: null;
    }

    /**
     * Get docker sync container name.
     *
     * @return string
     *   The docker sync container name.
     */
    public function getDockerSyncContainer()
    {
        return $this->getDockerSyncName() . '-docker-sync';
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
     * Get the docker ports to verify.
     *
     * @return array
     *   Ports that should be verified for use.
     */
    protected function getPortsToScan()
    {
        if ($this->hasTraefik()) {
            if ($this->isTraefikRunning()) {
                return [];
            }

            return self::TRAEFIK_PORTS;
        }

        return $this->requiredPorts();
    }

    /**
     * Update docker compose images.
     *
     * @return $this
     */
    protected function updateDockerComposeImages()
    {
        $this->taskDockerComposePull()
            ->quiet()
            ->run();

        return $this;
    }

    /**
     * Build docker composer services.
     *
     * @return $this
     */
    protected function buildDockerComposeServices()
    {
        $this->say('Docker compose build process is running...');
        $this->taskDockerComposeBuild()
            ->printOutput(false)
            ->pull()
            ->run();

        return $this;
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
     * Ask for the service name.
     *
     * @return string
     *   The service container name.
     */
    protected function askForServiceName()
    {
        return $this->doAsk(
            new ChoiceQuestion(
                'Select the service container:',
                array_keys($this->getServices())
            )
        );
    }

    /**
     * Ask for the service container identifier.
     *
     * @param null $service
     *   The service name without having to prompt.
     *
     * @return bool|string
     */
    protected function askForServiceContainerId($service = null)
    {
        if (!isset($service)) {
            $service = $this->askForServiceName();
        }
        $container_id = $this->getServiceContainerId($service);

        if ($container_id === false) {
            throw new EngineRuntimeException(
                sprintf('Unable to obtain the container identifier for the
                     %s service.', $service)
            );
        }

        return $container_id;
    }

    /**
     * Start the traefik container if not already running.
     */
    protected function startTraefik()
    {
        if ($this->hasTraefik()) {
            $this->createTraefikNetworkProxy();

            if (!$this->isTraefikRunning()) {
                $container = self::TRAEFIK_CONTAINER_NAME;

                if ($this->hasDockerContainer($container)) {
                    $result = $this->taskDockerStart($container)
                        ->run();
                } else {
                    $version = self::TRAEFIK_VERSION;
                    $result = $this->taskDockerRun("traefik:{$version}")
                        ->detached()
                        ->printOutput(false)
                        ->exec('--api --docker')
                        ->name($container)
                        ->option('publish', '80:80')
                        ->option('publish', '8080:8080')
                        ->option('network', self::TRAEFIK_NETWORK)
                        ->volume('/var/run/docker.sock', '/var/run/docker.sock')
                        ->run();
                }

                $this->validateTaskResult($result);
            }
        }

        return $this;
    }

    /**
     * Stop traefik project-x container.
     */
    protected function stopTraefik()
    {
        if ($this->hasTraefik()) {
            $container = self::TRAEFIK_CONTAINER_NAME;

            // Shutdown and remove the traefik container.
            if ($this->isTraefikRunning()
                && $this->hasDockerContainer($container)) {
                $this->say(sprintf('Container "%s" has been stopped.', $container));
                $this->taskDockerStop($container)
                    ->printOutput(false)
                    ->run();

                $this->say(sprintf('Container "%s" has been removed.', $container));
                $this->taskDockerRemove($container)
                    ->printOutput(false)
                    ->run();
            }

            // Remove the traefik network proxy.
            $this->removeTraefikNetworkProxy();
        }

        return $this;
    }

    /**
     * Create the traefik network proxy.
     */
    protected function createTraefikNetworkProxy()
    {
        $network = self::TRAEFIK_NETWORK;

        if (!$this->hasDockerNetwork($network)) {
            $this->say("Creating '{$network}' network...");
            $this->taskExec("docker network create {$network}")
                ->printOutput(false)
                ->run();
        }

        return $this;
    }

    /**
     * Remove the traefik network proxy.
     */
    protected function removeTraefikNetworkProxy()
    {
        $network = self::TRAEFIK_NETWORK;

        if ($this->hasDockerNetwork($network)) {
            $this->say("Removing '{$network}' network...");
            $this->taskExec("docker network rm {$network}")
                ->printOutput(false)
                ->run();
        }

        return $this;
    }

    /**
     * Has the docker network been defined.
     *
     * @param $name
     *   The container name.
     *
     * @return bool
     */
    protected function hasDockerNetwork($name)
    {
        /** @var ResultData $result */
        $result = $this->runSilentCommand(
            $this->taskExec("docker network ls --filter='name={$name}' -q")
        );
        $output = $result->getMessage();

        return isset($output) && !empty($output);
    }

    /**
     * Has the docker container been defined.
     *
     * @param $name
     *   The container name.
     *
     * @return bool
     */
    protected function hasDockerContainer($name)
    {
        /** @var ResultData $result */
        $result = $this->runSilentCommand(
            $this->taskExec("docker ps --filter='name={$name}' -q")
        );
        $output = $result->getMessage();

        return isset($output) && !empty($output);
    }

    /**
     * Determine if docker container is running.
     *
     * @param string $container
     *   The container name or identifier.
     *
     * @return bool
     */
    protected function isContainerRunning($container)
    {
        /** @var ResultData $result */
        $result = $this->runSilentCommand(
            $this->taskExec("docker inspect -f {{.State.Running}} {$container}")
        );

        return $result->getExitCode() === Resultdata::EXITCODE_OK
            && $result->getMessage() == 'true';
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
        $docker_compose->setVersion(static::DOCKER_VERSION);

        $has_proxy = $this->hasTraefik() ? true : false;

        foreach ($this->getServices() as $name => $info) {
            if (!isset($info['type'])) {
                continue;
            }
            $type = $info['type'];
            $instance = self::loadService($this, $type, $name);

            if (!$dev && $has_proxy) {
                $instance->setInternal();
                if (isset($info['bind_ports']) && $info['bind_ports']) {
                    $instance->bindPorts();
                }
            }

            if (isset($info['version'])) {
                $instance->setVersion($info['version']);
            }
            $volumes = $dev ? $instance->devVolumes() : $instance->volumes();
            $service = $dev ? $instance->devService() : $instance->getService();

            if (!empty($volumes)) {
                $docker_compose->setVolumes($volumes);
            }

            if (!$service->isEmpty()) {
                $docker_compose->setService($name, $service);
            }
        }

        if (!$dev && $has_proxy) {
            $docker_compose->setNetworks([
                'internal' => [
                    'external' => 'false',
                ],
                static::TRAEFIK_NETWORK => [
                    'external' => [
                        'name' => static::TRAEFIK_NETWORK
                    ]
                ]
            ]);
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
            $instance = self::loadService($this, $type, $name);

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
     * Show required ports table.
     *
     * @param array $ports
     *   An array of ports to check.
     *
     * @param string $host
     *   The host IP address to check.
     *
     * @return bool
     *   Return true if it's okay to continue; otherwise false.
     */
    protected function showRequiredPortsTable(array $ports, $host = '127.0.0.1')
    {
        $status = $this->getPortStatus($host, $ports);

        if (!empty($status)) {
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

        if (!empty($ports)) {
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
        }

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

        // Add docker compose dev configurations.
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
     * Run a command in docker container.
     *
     * @param $container_id
     *   The docker container id.
     * @param string|Command $command
     *   A string or command object.
     * @param array $options
     *   An array of command options.
     * @param bool $quiet
     *   Determine if the command should print output.
     * @param bool $interactive
     *   Determine if the command should run in interactive mode.
     *
     * @return ResultData
     */
    protected function runCommandInContainer(
        $container_id,
        $command,
        array $options = [],
        $quiet = false,
        $interactive = false
    ) {
        if (!isset($container_id) || !isset($command)) {
            return false;
        }
        /** @var Exec $docker_execute */
        $docker_execute = $this->taskDockerExec($container_id);

        if ($quiet) {
            $docker_execute->printOutput(false);
        }

        if ($interactive) {
            $docker_execute->interactive(true);
        }

        if (!empty($options)) {
            $docker_execute->options($options);
        }

        return $docker_execute
            ->exec("sh -c \"{$command}\"")
            ->run();
    }

    /**
     * Get service container identifier.
     *
     * @param string $container
     *   The container name.
     *
     * @return bool|string
     *   Return container id; otherwise false if error occurred.
     */
    protected function getServiceContainerId($container)
    {
        if (!isset($container)) {
            return false;
        }
        /** @var Ps $task */
        $task = $this->taskDockerComposePs();

        $result = $task
            ->printOutput(false)
            ->setService($container)
            ->quiet()
            ->run();

        if ($result->getExitCode() !== ResultData::EXITCODE_OK) {
            return false;
        }

        return $result->getMessage();
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
