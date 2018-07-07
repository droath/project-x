<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\CommandBuilder;
use Droath\ProjectX\ComposerPackageInterface;
use Droath\ProjectX\Config\ComposerConfig;
use Droath\ProjectX\Database;
use Droath\ProjectX\DatabaseInterface;
use Droath\ProjectX\DeployAwareInterface;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\EngineServiceInterface;
use Droath\ProjectX\Engine\EngineType;
use Droath\ProjectX\Engine\ServiceDbInterface;
use Droath\ProjectX\Project\Command\MysqlCommand;
use Droath\ProjectX\Project\Command\PgsqlCommand;
use Droath\ProjectX\ProjectX;
use Robo\Task\Composer\loadTasks as composerTasks;
use Robo\Task\Filesystem\loadTasks as fileSystemTasks;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Define PHP project type.
 */
abstract class PhpProjectType extends ProjectType implements DeployAwareInterface, EngineServiceInterface
{
    use composerTasks;
    use fileSystemTasks;

    /**
     * Service constants.
     */
    const DEFAULT_PHP7 = 7.1;
    const DEFAULT_PHP5 = 5.6;
    const DEFAULT_MYSQL = '5.6';
    const DEFAULT_APACHE = '2.4';

    /**
     * Package versions.
     */
    const PHPCS_VERSION = '2.*';
    const BEHAT_VERSION = '^3.1';
    const PHPUNIT_VERSION = '>=4.8.28 <5';

    /**
     * Composer instance.
     *
     * @var \Droath\ProjectX\Config\ComposerConfig
     */
    protected $composer;

    /**
     * Database override.
     *
     * @var DatabaseInterface
     */
    protected $databaseOverride;

    /**
     * Constructor for PHP project type.
     */
    public function __construct()
    {
        $this->composer = $this->composer();
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        parent::build();

        $this
            ->askTravisCi()
            ->askProboCi()
            ->askBehat()
            ->askPhpUnit()
            ->askPhpCodeSniffer();
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        parent::install();

        $this->initBehat();
    }

    /**
     * {@inheritdoc}
     */
    public function taskDirectories()
    {
        return array_merge([
            APP_ROOT . '/src/Project/Task/PHP'
        ], parent::taskDirectories());
    }

    /**
     * {@inheritdoc}
     */
    public function templateDirectories()
    {
        return array_merge([
            APP_ROOT . '/templates/php'
        ], parent::templateDirectories());
    }

    /**
     * {@inheritdoc}
     */
    public function defaultServices()
    {
        return [
            'php' => [
                'type' => 'php',
                'version' => static::DEFAULT_PHP7
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function serviceConfigs()
    {
        $configs = [
            'php' => [
                'PACKAGE_INSTALL' => [
                    'file',
                    'libpng12-dev',
                    'libjpeg-dev',
                    'libwebp-dev',
                    'libpq-dev',
                    'libmcrypt-dev',
                    'libmagickwand-dev'
                ],
                'PHP_PECL' => [
                    'redis',
                    'xdebug',
                    'imagick:3.4.3'
                ],
                'PHP_EXT_ENABLE' => [],
                'PHP_EXT_CONFIG' => [
                    'gd --with-png-dir=/usr --with-jpeg-dir=/usr  --with-webp-dir=/usr'
                ],
                'PHP_EXT_INSTALL' => [
                    'gd',
                    'zip',
                    'pdo',
                    'mcrypt',
                    'opcache',
                    'mbstring',
                ],
                'PHP_COMMANDS' => [
                    'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer'
                ]
            ]
        ];

        /** @var EngineType $engine */
        $engine = $this->getEngineInstance();

        /** @var ServiceDbInterface $service */
        $service = $engine->getServiceInstanceByInterface(ServiceDbInterface::class);

        switch ($service->protocol()) {
            case 'mysql':
                $configs['php']['PACKAGE_INSTALL'][] = 'mysql-client';
                $configs['php']['PHP_EXT_INSTALL'][] =  'pdo_mysql';
                break;
            case 'pgsql':
                $configs['php']['PACKAGE_INSTALL'][] = 'postgresql-client';
                $configs['php']['PHP_EXT_INSTALL'][] = 'pdo_pgsql';
                break;
        }

        return $configs;
    }

    /**
     * Import database dump into a service.
     *
     * @param null $service
     *   The database service name to use.
     * @param null $import_path
     *   The path to the database file.
     * @param bool $copy_to_service
     *   Copy imported file to service.
     * @param bool $localhost
     *   Flag to determine if the command should be ran from localhost.
     *
     * @return $this
     * @throws \Exception
     */
    public function importDatabaseToService($service = null, $import_path = null, $copy_to_service = false, $localhost = false)
    {
        $destination = $this->resolveDatabaseImportDestination(
            $import_path,
            $service,
            $copy_to_service,
            $localhost
        );

        /** @var CommandBuilder $command */
        $command = $this->resolveDatabaseImportCommand()->command("< {$destination}");

        $this->executeEngineCommand($command, $service, [], false, $localhost);

        return $this;
    }

    /**
     * Set the database override object.
     *
     * @param DatabaseInterface $database
     *
     * @return $this
     */
    public function setDatabaseOverride(DatabaseInterface $database)
    {
        $this->databaseOverride = $database;

        return $this;
    }

    /**
     * Get database information based on services.
     *
     * @param ServiceDbInterface|null $instance
     *   Instance of the environment engine DB service.
     * @param bool $allow_override
     *   Set if to false to not include overrides.
     *
     * @return DatabaseInterface
     */
    public function getDatabaseInfo(ServiceDbInterface $instance = null, $allow_override = true)
    {
        if (!isset($instance)) {
            /** @var EngineType $engine */
            $engine = $this->getEngineInstance();
            $instance = $engine->getServiceInstanceByInterface(
                ServiceDbInterface::class
            );

            if ($instance === false) {
                throw new \RuntimeException(
                    'Unable to find a service for the database instance.'
                );
            }
        }
        $database = $this->getServiceInstanceDatabase($instance);

        if (!$allow_override || !isset($this->databaseOverride)) {
            return $database;
        }

        // Process database overrides on the current db object.
        foreach (get_object_vars($this->databaseOverride) as $property => $value) {
            if (empty($value)) {
                continue;
            }
            $method = 'set' . ucwords($property);

            if (!method_exists($database, $method)) {
                continue;
            }
            $database = call_user_func_array(
                [$database, $method],
                [$value]
            );
        }

        return $database;
    }

    /**
     * Get environment PHP version.
     *
     * @return string
     *   The PHP version defined by the environment engine service.
     */
    public function getEnvPhpVersion()
    {
        /** @var EngineType $engine */
        $engine = $this->getEngineInstance();
        $instance = $engine->getServiceInstanceByType('php');

        if (empty($instance)) {
            throw new \RuntimeException(
                'No php service has been found.'
            );
        }
        $service = $instance[0];

        return $service->getVersion();
    }

    /**
     * Setup TravisCi configurations.
     *
     * The setup steps consist of the following:
     *   - Copy .travis.yml to project root.
     */
    public function setupTravisCi()
    {
        $this->copyTemplateFileToProject('.travis.yml', true);

        return $this;
    }

    /**
     * Setup ProboCi configurations.
     *
     * The setup steps consist of the following:
     *   - Copy .probo.yml to project root.
     */
    public function setupProboCi()
    {
        $filename = ProjectX::projectRoot() . '/.probo.yml';
        $this->taskWriteToFile($filename)
            ->text($this->loadTemplateContents('.probo.yml'))
            ->place('PROJECT_ROOT', $this->getInstallRoot())
            ->run();

        return $this;
    }

    /**
     * Setup Behat.
     *
     * The setup steps consist of the following:
     *   - Make tests/Behat directories in project root.
     *   - Copy behat.yml to tests/Behat directory.
     *   - Add behat package to composer instance.
     *
     * @return self
     */
    public function setupBehat()
    {
        $root_path = ProjectX::projectRoot();
        $behat_path = "{$root_path}/tests/Behat/behat.yml";

        $this->taskFilesystemStack()
            ->mkdir("{$root_path}/tests/Behat", 0775)
            ->copy($this->getTemplateFilePath('tests/behat.yml'), $behat_path)
            ->run();

        $this->composer->addRequires([
            'behat/behat' => static::BEHAT_VERSION,
        ], true);

        return $this;
    }

    /**
     * Initialize Behat for the project.
     *
     * @return self
     */
    public function initBehat()
    {
        $root_path = ProjectX::projectRoot();

        if ($this->hasBehat()
            && !file_exists("$root_path/tests/Behat/features")) {
            $this->taskBehat()
                ->option('init')
                ->option('config', "{$root_path}/tests/Behat/behat.yml")
                ->run();
        }

        return $this;
    }

    /**
     * Has Behat in composer.json.
     */
    public function hasBehat()
    {
        return $this->hasComposerPackage('behat/behat', true);
    }

    /**
     * Setup PHPunit configurations.
     */
    public function setupPhpUnit()
    {
        $root_path = ProjectX::projectRoot();

        $this->taskFilesystemStack()
            ->mkdir("{$root_path}/tests/PHPunit", 0775)
            ->copy($this->getTemplateFilePath('tests/bootstrap.php'), "{$root_path}/tests/bootstrap.php")
            ->copy($this->getTemplateFilePath('tests/phpunit.xml.dist'), "{$root_path}/phpunit.xml.dist")
            ->run();

        $this->composer->addRequires([
            'phpunit/phpunit' => static::PHPUNIT_VERSION,
        ], true);

        return $this;
    }

    /**
     * Has PHPunit in composer.json.
     */
    public function hasPhpUnit()
    {
        return $this->hasComposerPackage('phpunit/phpunit', true);
    }

    /**
     * Setup PHP code sniffer.
     */
    public function setupPhpCodeSniffer()
    {
        $root_path = ProjectX::projectRoot();

        $this->taskWriteToFile("{$root_path}/phpcs.xml.dist")
            ->text($this->loadTemplateContents('phpcs.xml.dist'))
            ->place('PROJECT_ROOT', $this->getInstallRoot())
            ->run();

        $this->composer->addRequires([
            'squizlabs/php_codesniffer' => static::PHPCS_VERSION,
        ], true);

        return $this;
    }

    /**
     * Has PHP code sniffer in composer.json.
     *
     * @return boolean
     */
    public function hasPhpCodeSniffer()
    {
        return $this->hasComposerPackage('squizlabs/php_codesniffer', true);
    }

    /**
     * Get PHP service name.
     *
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getPhpServiceName()
    {
        $names = $this->getEngineServiceNamesByType('php');
        return reset($names);
    }

    /**
     * Get composer instance.
     *
     * @return \Droath\ProjectX\Config\ComposerConfig
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * Save changes to the composer.json.
     *
     * @return self
     */
    public function saveComposer()
    {
        $this->composer
            ->save($this->composerFile());

        return $this;
    }

    /**
     * Package PHP build.
     *
     * The process consist of following:
     *   - Copy patches.
     *   - Copy composer.json and composer.lock
     *
     * @param $build_root
     *   The build root path.
     * @return self
     */
    public function packagePhpBuild($build_root)
    {
        $project_root = ProjectX::projectRoot();

        $stack = $this->taskFilesystemStack();
        if (file_exists("{$project_root}/patches")) {
            $stack->mirror("{$project_root}/patches", "{$build_root}/patches");
        }
        $stack->copy("{$project_root}/composer.json", "{$build_root}/composer.json");
        $stack->copy("{$project_root}/composer.lock", "{$build_root}/composer.lock");
        $stack->run();

        return $this;
    }

    /**
     * Update composer packages.
     *
     * @param bool $lock
     *   Determine to update composer using --lock.
     *
     * @return self
     */
    public function updateComposer($lock = false)
    {
        $update = $this->taskComposerUpdate();

        if ($lock) {
            $update->option('lock');
        }
        $update->run();

        return $this;
    }

    /**
     * Install composer packages.
     *
     * @return $this
     */
    public function installComposer()
    {
        $this->taskComposerInstall()
            ->run();

        return $this;
    }

    /**
     * Has composer package.
     *
     * @param string $vendor
     *   The package vendor project namespace.
     * @param boolean $dev
     *   A flag defining if it's a dev requirement.
     *
     * @return boolean
     */
    public function hasComposerPackage($vendor, $dev = false)
    {

        $packages = !$dev
            ? $this->composer->getRequire()
            : $this->composer->getRequireDev();

        return isset($packages[$vendor]);
    }

    /**
     * {@inheritdoc}
     */
    public function onDeployBuild($build_root)
    {
        parent::onDeployBuild($build_root);

        $this->packagePhpBuild($build_root);

        $update_result = $this->taskComposerUpdate()
            ->noDev()
            ->preferDist()
            ->workingDir($build_root)
            ->option('lock')
            ->run();
        $this->validateTaskResult($update_result);

        $install_result = $this->taskComposerInstall()
            ->noDev()
            ->preferDist()
            ->option('quiet')
            ->noInteraction()
            ->workingDir($build_root)
            ->optimizeAutoloader()
            ->run();
        $this->validateTaskResult($install_result);
    }

    /**
     * Ask to setup TravisCI configurations.
     *
     * @return self
     */
    protected function askTravisCi()
    {
        if ($this->askConfirmQuestion('Setup TravisCI?', false)) {
            $this->setupTravisCi();
        }

        return $this;
    }

    /**
     * Ask to setup ProboCI configurations.
     *
     * @return self
     */
    protected function askProboCi()
    {
        if ($this->askConfirmQuestion('Setup ProboCI?', false)) {
            $this->setupProboCi();
        }

        return $this;
    }

   /**
     * Ask to setup Behat.
     *
     * @return self
     */
    protected function askBehat()
    {
        if ($this->askConfirmQuestion('Setup Behat?', false)) {
            $this->setupBehat();
        }

        return $this;
    }

    /**
     * Ask to setup PHPunit.
     *
     * @return self
     */
    protected function askPhpUnit()
    {
        if ($this->askConfirmQuestion('Setup PHPUnit?', false)) {
            $this->setupPhpUnit();
        }

        return $this;
    }

    /**
     * Ask to setup PHP code sniffer.
     *
     * @return self
     */
    protected function askPhpCodeSniffer()
    {
        if ($this->askConfirmQuestion('Setup PHP code sniffer?', false)) {
            $this->setupPhpCodeSniffer();
        }

        return $this;
    }

    /**
     * Database info mapping keys.
     *
     * @return array
     *   An array of database key mapping.
     */
    protected function databaseInfoMapping()
    {
        return [];
    }

    /**
     * Extract archive within appropriate environment.
     *
     * @param $filename
     *   The path to the file archive.
     * @param $destination
     *   The destination path of extracted data.
     * @param string|null $service
     *   The service name.
     * @param bool $localhost
     *   Extract archive on host.
     * @return null|boolean
     * @throws \Exception
     */
    protected function extractArchive($filename, $destination, $service = null, $localhost = false)
    {
        $mime_type = null;

        if (file_exists($filename) && $localhost) {
            $mime_type = mime_content_type($filename);
        } else {
            $engine = $this->getEngineInstance();

            if ($engine instanceof DockerEngineType) {
                $mime_type = $engine->getFileMimeType($filename, $service);
            }
        }
        $command = null;

        switch ($mime_type) {
            case 'application/gzip':
            case 'application/x-gzip':
                $command = (new CommandBuilder('gunzip', $localhost))
                    ->command("-c {$filename} > {$destination}");
                break;
        }

        if (!isset($command)) {
            return $filename;
        }

        // Remove destination file if on localhost.
        if (file_exists($destination) && $localhost) {
            $this->_remove($destination);
        }
        $this->executeEngineCommand($command, $service, [], false, $localhost);

        return $destination;
    }

    /**
     * Resolve database import path.
     *
     * @param string|null $import_path
     *   The path to use for the database import.
     *
     * @return \SplFileInfo
     *   The database import path.
     * @throws \Exception
     */
    protected function resolveDatabaseImportPath($import_path = null)
    {
        if (!isset($import_path)) {
            $import_path = $this->doAsk(
                new Question(
                    'Input the path to the database file: '
                )
            );

            if (!file_exists($import_path)) {
                throw new \Exception(
                    'The path to the database file does not exist.'
                );
            }
        }

        return new \SplFileInfo($import_path);
    }

    /**
     * Resolve database import destination.
     *
     * @param $import_path
     *   The database import path.
     * @param $service
     *   The service name.
     * @param bool $copy_to_service
     *   Copy imported file to service.
     * @param bool $localhost
     *   Resolve destination path from host.
     * @return bool|null
     * @throws \Exception
     */
    protected function resolveDatabaseImportDestination($import_path, $service, $copy_to_service = false, $localhost = false)
    {
        /** @var \SplFileInfo $import_path */
        $path = $this->resolveDatabaseImportPath($import_path);

        $filename = $path->getFilename();
        $extract_filename = substr($filename, 0, strrpos($filename, '.'));

        if (!$localhost) {
            // Copy file to service if uploaded from localhost.
            if ($copy_to_service) {
                /** @var DockerEngineType $engine */
                $engine = $this->getEngineInstance();

                if ($engine instanceof DockerEngineType) {
                    $engine->copyFileToService($path->getRealPath(), '/tmp', $service);
                }
            }

            return $this->extractArchive(
                "/tmp/{$filename}",
                "/tmp/{$extract_filename}",
                $service,
                $localhost
            );
        }

        return $this->extractArchive(
            $path->getRealPath(),
            "/tmp/{$extract_filename}",
            null,
            $localhost
        );
    }

    /**
     * Resolve database service instance.
     *
     * @param null $service
     *   The database service name.
     *
     * @return mixed
     */
    protected function resolveDatabaseServiceInstance($service = null)
    {
        /** @var EngineType $engine */
        $engine = $this->getEngineInstance();
        $services = $engine->getServiceInstanceByGroup('database');

        if (!isset($service) || !isset($services[$service])) {
            if (count($services) > 1) {
                $options = array_keys($services);
                $service = $this->doAsk(
                    new ChoiceQuestion(
                        'Select the database service to use for import: ',
                        $options
                    )
                );
            } else {
                $options = array_keys($services);
                $service = reset($options);
            }
        }

        if (!isset($services[$service])) {
            throw new \RuntimeException(
                'Unable to resolve database service.'
            );
        }

        return $services[$service];
    }

    /**
     * Resolve database import command.
     *
     * @param string|null $service
     *   The database service name.
     *
     * @return CommandBuilder
     *   The database command based on the provide service.
     */
    protected function resolveDatabaseImportCommand($service = null)
    {
        /** @var ServiceDbInterface $instance */
        $instance = $this->resolveDatabaseServiceInstance($service);

        /** @var DatabaseInterface $database */
        $database = $this->getDatabaseInfo($instance);

        switch ($database->getProtocol()) {
            case 'mysql':
                return (new MysqlCommand())
                    ->host($database->getHostname())
                    ->username($database->getUser())
                    ->password($database->getPassword())
                    ->database($database->getDatabase());
            case 'pgsql':
                return (new PgsqlCommand())
                    ->host($database->getHostname())
                    ->username($database->getUser())
                    ->password($database->getPassword())
                    ->database($database->getDatabase());
        }
    }

    /**
     * Check if host has database connection.
     *
     * @param string $host
     *   The database hostname.
     * @param int $port
     *   The database port.
     * @param int $seconds
     *   The amount of seconds to continually check.
     *
     * @return bool
     *   Return true if the database is connectible; otherwise false.
     */
    protected function hasDatabaseConnection($host, $port = 3306, $seconds = 30)
    {
        $hostChecker = $this->getHostChecker();
        $hostChecker
            ->setHost($host)
            ->setPort($port);

        return $hostChecker->isPortOpenRepeater($seconds);
    }

    /**
     * Get a service instance database object.
     *
     * @param ServiceDbInterface $instance
     *   The service database instance.
     *
     * @return Database
     *   The database object.
     */
    protected function getServiceInstanceDatabase(ServiceDbInterface $instance)
    {
        $port = current($instance->getHostPorts());

        return (new Database($this->databaseInfoMapping()))
            ->setPort($port)
            ->setUser($instance->username())
            ->setPassword($instance->password())
            ->setProtocol($instance->protocol())
            ->setHostname($instance->getName())
            ->setDatabase($instance->database());
    }

    /**
     * Merge project composer template.
     *
     * This will try and load a composer.json template from the project root. If
     * not found it will search in the application template root for the
     * particular project type.
     *
     * The method only exist so that projects can merge in composer requirements
     * during the project build cycle. If those requirements were declared in
     * the composer.json root, and dependencies are needed based on the project
     * type on which haven't been added yet, can cause issues.
     *
     * @return self
     */
    protected function mergeProjectComposerTemplate()
    {
        if ($contents = $this->loadTemplateContents('composer.json', 'json')) {
            $this->composer = $this->composer->update($contents);
        }

        return $this;
    }

    /**
     * Setup composer packages.
     *
     * @return $this
     */
    protected function setupComposerPackages()
    {
        $platform = $this->getPlatformInstance();

        if ($platform instanceof ComposerPackageInterface) {
            $platform->alterComposer($this->composer);
        }
        $this->saveComposer();

        return $this;
    }

    /**
     * Composer config instance.
     *
     * @return \Droath\ProjectX\Config\ComposerConfig
     */
    private function composer()
    {
        $composer_file = $this->composerFile();

        return file_exists($composer_file)
            ? ComposerConfig::createFromFile($composer_file)
            : new ComposerConfig();
    }

    /**
     * Get composer file object.
     *
     * @return \splFileInfo
     */
    private function composerFile()
    {
        return new \splFileInfo(
            ProjectX::projectRoot() . '/composer.json'
        );
    }
}
