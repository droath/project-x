<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\CommandBuilder;
use Droath\ProjectX\Config\ComposerConfig;
use Droath\ProjectX\Database;
use Droath\ProjectX\DatabaseInterface;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\EngineType;
use Droath\ProjectX\Engine\ServiceDbInterface;
use Droath\ProjectX\Project\Command\MysqlCommand;
use Droath\ProjectX\Project\Command\PgsqlCommand;
use Droath\ProjectX\ProjectX;
use Robo\ResultData;
use Robo\Task\Composer\loadTasks as composerTasks;
use Robo\Task\Filesystem\loadTasks as fileSystemTasks;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Define PHP project type.
 */
abstract class PhpProjectType extends ProjectType
{
    use composerTasks;
    use fileSystemTasks;

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
     * Import database dump into a service.
     *
     * @param null $service
     *   The database service name to use.
     * @param null $import_path
     *   The path to the database file.
     * @param bool $localhost
     *   Flag to determine if the command should be ran from localhost.
     *
     * @return $this
     * @throws \Exception
     */
    public function importDatabaseToService($service = null, $import_path = null, $localhost = false)
    {
        /** @var EngineType $engine */
        $engine = $this->getEngineInstance();
        /** @var ServiceDbInterface $instance */
        $instance = $this->resolveDatabaseServiceInstance($service);

        if ($engine instanceof DockerEngineType) {
            $import = $this->resolveDatabaseImportCommand($instance);
            $import_path = $this->resolveDatabaseImportPath($import_path);

            if (!$localhost) {
                $service_name = $instance->getName();
                $status = $engine
                    ->copyFileToService($import_path, '/tmp', $service_name);

                if ($status->getExitCode() === ResultData::EXITCODE_OK) {
                    $filename = basename($import_path);
                    $command = $import->command("< /tmp/{$filename}");

                    $engine->execRaw(
                        $command->build(),
                        $service_name
                    );
                }
            } else {
                $command = $import->command("< {$import_path}");
                $this->_exec($command->build());
            }
        } else {
            throw new \Exception(
                "Environment engine doesn't support database import."
            );
        }

        return $this;
    }

    /**
     * Get database information based on services.
     *
     * @param ServiceDbInterface|null $instance
     *
     * @return DatabaseInterface
     * @throws \RuntimeException
     */
    public function getDatabaseInfo(ServiceDbInterface $instance = null)
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

        return $this->getServiceInstanceDatabase($instance);
    }

    /**
     * Get database info with overrides.
     *
     * @param DatabaseInterface $database
     *   A database object that contains properties to override.
     *
     * @return DatabaseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getDatabaseInfoWithOverrides(DatabaseInterface $database)
    {
        $default_database = $this->getDatabaseInfo();

        // Set the override database value for given properties.
        foreach (get_object_vars($database) as $property => $value) {
            if (empty($value)) {
                continue;
            }
            $method = 'set' . ucwords($property);

            if (!method_exists($default_database, $method)) {
                continue;
            }
            $default_database = call_user_func_array(
                [$default_database, $method],
                [$value]
            );
        }

        return $default_database;
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
     * @return self
     */
    public function updateComposer()
    {
        $this->taskComposerUpdate()
            ->run();

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
        if ($this->askConfirmQuestion('Setup TravisCI?', true)) {
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
        if ($this->askConfirmQuestion('Setup ProboCI?', true)) {
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
        if ($this->askConfirmQuestion('Setup Behat?', true)) {
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
        if ($this->askConfirmQuestion('Setup PHPUnit?', true)) {
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
        if ($this->askConfirmQuestion('Setup PHP code sniffer?', true)) {
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
     * Resolve database import path.
     *
     * @param string|null $import_path
     *   The path to use for the database import.
     *
     * @return string
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

        return trim($import_path);
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
     * @param ServiceDbInterface $instance
     *   The database service instance.
     *
     * @return CommandBuilder
     *   The database command based on the provide service.
     * @throws \Exception
     */
    protected function resolveDatabaseImportCommand(ServiceDbInterface $instance)
    {
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
     * Has composer package.
     *
     * @param string $vendor
     *   The package vendor project namespace.
     * @param boolean $dev
     *   A flag defining if it's a dev requirement.
     *
     * @return boolean
     */
    protected function hasComposerPackage($vendor, $dev = false)
    {
        $packages = !$dev
            ? $this->composer->getRequire()
            : $this->composer->getRequireDev();

        return isset($packages[$vendor]);
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
