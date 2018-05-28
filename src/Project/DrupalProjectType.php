<?php

namespace Droath\ProjectX\Project;

use Boedah\Robo\Task\Drush\DrushStack;
use Boedah\Robo\Task\Drush\loadTasks as drushTasks;
use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ProjectX\CommandBuilder;
use Droath\ProjectX\Config\ComposerConfig;
use Droath\ProjectX\Database;
use Droath\ProjectX\DatabaseInterface;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Exception\TaskResultRuntimeException;
use Droath\ProjectX\OptionFormAwareInterface;
use Droath\ProjectX\Project\Command\DrushCommand;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubTypeInterface;
use Droath\ProjectX\Utility;
use Robo\ResultData;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;

/**
 * Define Drupal project type.
 */
class DrupalProjectType extends PhpProjectType implements TaskSubTypeInterface, OptionFormAwareInterface
{
    /**
     * Composer package version constants.
     */
    const DRUSH_VERSION = '^8.1';
    const DRUPAL_8_VERSION = '^8.5';

    /**
     * Service constants.
     */
    const DEFAULT_PHP7 = 7.1;
    const DEFAULT_PHP5 = 5.6;
    const DEFAULT_MYSQL = '5.6';
    const DEFAULT_APACHE = '2.4';

    /**
     * Project supported versions.
     */
    const DEFAULT_VERSION = 8;
    const SUPPORTED_VERSIONS = [
        7 => 7,
        8 => 8
    ];

    use drushTasks;

    /**
     * Define the Drupal sites path.
     *
     * @var string
     */
    protected $sitesPath;

    /**
     * Define settings file path.
     *
     * @var string
     */
    protected $settingFile;

    /**
     * Define local setting file path.
     *
     * @var string
     */
    protected $settingLocalFile;

    /**
     * Constructor for the Drupal project type.
     */
    public function __construct()
    {
        parent::__construct();

        $install_path = $this->getInstallPath();

        // Drupal sites common file/directory locations.
        $this->sitesPath = "{$install_path}/sites";
        $this->sitesFiles = "{$this->sitesPath}/default/files";

        // Drupal settings file.
        $this->settingFile = "{$this->sitesPath}/default/settings.php";
        $this->settingLocalFile = "{$this->sitesPath}/default/settings.local.php";

        // Drupal project supports Docker engines.
        $this->supportsDocker();
    }

    /**
     * {@inheritdoc}.
     */
    public static function getlabel()
    {
        return 'Drupal';
    }

    /**
     * {@inheritdoc}.
     */
    public static function getTypeId()
    {
        return 'drupal';
    }

    /**
     * {@inheritdoc}
     */
    public function defaultServices()
    {
        return [
            'web' => [
                'type' => 'apache',
                'version' => static::DEFAULT_APACHE,
            ],
            'php' => [
                'type' => 'php',
                'version' => $this->getProjectVersion() === 8
                    ? static::DEFAULT_PHP7
                    : static::DEFAULT_PHP5,
            ],
            'database' => [
                'type' => 'mysql',
                'version' => static::DEFAULT_MYSQL,
            ]
        ];
    }

    /**
     * {@inheritdoc}.
     */
    public function taskDirectories()
    {
        return array_merge([
            APP_ROOT . '/src/Project/Task/Drupal'
        ], parent::taskDirectories());
    }

    /**
     * {@inheritdoc}.
     */
    public function templateDirectories()
    {
        return array_merge([
            APP_ROOT . '/templates/drupal'
        ], parent::templateDirectories());
    }



    /**
     * Export Drupal configuration.
     *
     * @return self
     */
    public function exportDrupalConfig()
    {
        $version = $this->getProjectVersion();

        if ($version >= 8) {
            $this->runDrushCommand('cex');
            $this->saveDrupalUuid();
        }

        return $this;
    }

    /**
     * Drupal import configurations.
     *
     * @param int $reimport_attempts
     *   Set the amount of reimport attempts to invoke.
     * @param bool $localhost
     *   Determine if the drush command should be ran on the host.
     *
     * @return $this
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function importDrupalConfig($reimport_attempts = 1, $localhost = false)
    {
        if ($this->getProjectVersion() >= 8) {
            try {
                $drush = (new DrushCommand())
                    ->command('cr')
                    ->command('cim');

                $this->runDrushCommand($drush, false, $localhost);
            } catch (TaskResultRuntimeException $exception) {
                if ($reimport_attempts < 1) {
                    throw $exception;
                }
                $errors = 0;
                $result = null;

                // Attempt to resolve import issues by reimporting the
                // configurations again. This workaround was added due to
                // the following issue:
                // @see https://www.drupal.org/project/drupal/issues/2923899
                for ($i = 0; $i < $reimport_attempts; $i++) {
                    $result = $this->runDrushCommand('cim', false, $localhost);

                    if ($result->getExitCode() === ResultData::EXITCODE_OK) {
                        break;
                    }

                    ++$errors;
                }

                if (!isset($result)) {
                    throw new \Exception('Missing result object.');
                } else if ($errors == $reimport_attempts) {
                    throw new TaskResultRuntimeException($result);
                }
            }
        }

        return $this;
    }

    /**
     * Drupal option form object.
     *
     * @return \Droath\ConsoleForm\Form
     */
    public function optionForm()
    {
        $fields = [];
        $default = $this->defaultInstallOptions();

        $fields[] = (new BooleanField('site', 'Setup Drupal site options?'))
            ->setDefault(false)
            ->setSubform(function ($subform, $value) use ($default) {
                if ($value === true) {
                    $subform->addFields([
                        (new TextField('name', 'Drupal site name?'))
                            ->setDefault($default['site']['name']),
                        (new TextField('profile', 'Drupal site profile?'))
                            ->setDefault($default['site']['profile']),
                    ]);
                }
            });

        $fields[] = (new BooleanField('account', 'Setup Drupal account options?'))
            ->setDefault(false)
            ->setSubform(function ($subform, $value) use ($default) {
                if ($value === true) {
                    $subform->addFields([
                        (new TextField('mail', 'Account email:'))
                            ->setDefault($default['account']['mail']),
                        (new TextField('name', 'Account username:'))
                            ->setDefault($default['account']['name']),
                        (new TextField('pass', 'Account password:'))
                            ->setHidden(true)
                            ->setDefault($default['account']['pass']),
                    ]);
                }
            });

        return (new Form())
            ->addFields($fields);
    }

    /**
     * {@inheritdoc}.
     */
    public function install()
    {
        if (!$this->canInstall()) {
            $this->say(
                "Unable to install since the project hasn't been built yet. ⛈️"
            );

            return;
        }
        parent::install();

        $this
            ->setupProject()
            ->setupDrupalFilesystem()
            ->setupDrupalSettings()
            ->setupDrupalLocalSettings()
            ->projectEnvironmentUp()
            ->setupDrupalInstall()
            ->exportDrupalConfig()
            ->projectLaunchBrowser();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onEngineUp()
    {
        // Ensure default files permissions are 0775.
        $this->_chmod($this->sitesFiles, 0775, 0000, true);
    }

    /**
     * {@inheritdoc}
     */
    public function onDeployBuild($build_root)
    {
        parent::onDeployBuild($build_root);

        // Git submodules cause problems when committing code in a
        // parent repository. It could lead to missing files/directories.
        $this->removeGitSubmodulesInVendor($build_root);
        $this->removeGitSubmoduleInInstallPath($build_root);

        $this->packageDrupalBuild($build_root);
    }

    /**
     * {@inheritdoc}
     */
    public function setupBehat()
    {
        parent::setupBehat();

        if ($this->hasBehat()) {
            $this->composer
                ->addDevRequire('drupal/drupal-extension', '^3.2');
        }
        $root_path = ProjectX::projectRoot();

        $this->taskWriteToFile("{$root_path}/tests/Behat/behat.yml")
            ->append()
            ->place('PROJECT_ROOT', substr(static::installRoot(), 1))
            ->run();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setupPhpCodeSniffer()
    {
        parent::setupPhpCodeSniffer();

        if ($this->hasPhpCodeSniffer()) {
            $this->composer
                ->addDevRequire('drupal/coder', '^8.2');
        }

        return $this;
    }

    /**
     * Setup project.
     *
     * The setup process consist of the following:
     *   - Copy over .gitignore file to project root.
     *
     * @return self
     */
    public function setupProject()
    {
        $this->taskWriteToFile(ProjectX::projectRoot() . '/.gitignore')
            ->text($this->loadTemplateContents('.gitignore.txt'))
            ->place('PROJECT_ROOT', $this->getInstallRoot(true))
            ->run();

        return $this;
    }

    /**
     * Setup project composer requirements.
     *
     * @return self
     */
    public function setupProjectComposer()
    {
        $this->mergeProjectComposerTemplate();
        $install_root = substr(static::installRoot(), 1);
        $version = $this->getProjectVersion();

        $this->composer
            ->setType('project')
            ->setPreferStable(true)
            ->setMinimumStability('dev')
            ->setConfig([
                'platform' => [
                    'php' => "{$this->getEnvPhpVersion()}"
                ]
            ])
            ->addRepository('drupal', [
                'type' => 'composer',
                'url' =>  "https://packages.drupal.org/{$version}"
            ])
            ->addRequires([
                'drupal/core' => static::DRUPAL_8_VERSION,
                'composer/installers' => '^1.1',
                'cweagans/composer-patches' =>  '^1.5',
                'drupal-composer/drupal-scaffold' => '^2.0'
            ])
            ->addExtra('drupal-scaffold', [
                'excludes' => [
                    'robot.txt'
                ],
                'initial' => [
                    'sites/development.services.yml' => 'sites/development.services.yml',
                    'sites/example.settings.local.php' => 'sites/example.settings.local.php'
                ],
                'omit-defaults' => false
            ])
            ->addExtra('installer-paths', [
                $install_root . '/core' => ['type:drupal-core'],
                $install_root . '/modules/contrib/{$name}' => ['type:drupal-module'],
                $install_root . '/profiles/custom/{$name}' => ['type:drupal-profile'],
                $install_root . '/themes/contrib/{$name}'=> ['type:drupal-theme'],
                'drush/contrib/{$name}'=> ['type:drupal-drush']
            ]);

        return $this;
    }

    /**
     * Package up Drupal into a build directory.
     *
     * The process consist of the following:
     *   - Copy config
     *   - Copy salt.txt, index.php, settings.php, .htaccess, robots.txt,
     *     update.php, and web.config.
     *   - Copy themes, modules, and profile custom code.
     * @param $build_root
     *   The build root path.
     * @return self
     */
    public function packageDrupalBuild($build_root)
    {
        $project_root = ProjectX::projectRoot();
        $build_install = $build_root . static::installRoot();
        $install_path = $this->getInstallPath();

        $stack = $this->taskFilesystemStack();
        $static_files = [
            "{$project_root}/salt.txt" => "{$build_root}/salt.txt",
            "{$install_path}/.htaccess" => "{$build_install}/.htaccess",
            "{$install_path}/index.php" => "{$build_install}/index.php",
            "{$install_path}/robots.txt" => "{$build_install}/robots.txt",
            "{$install_path}/update.php" => "{$build_install}/update.php",
            "{$install_path}/web.config" => "{$build_install}/web.config",
            "{$install_path}/sites/default/settings.php" => "{$build_install}/sites/default/settings.php",
        ];

        foreach ($static_files as $source => $destination) {
            if (!file_exists($source)) {
                continue;
            }
            $stack->copy($source, $destination);
        }

        $mirror_directories = [
            '/config',
            static::installRoot() . '/libraries',
            static::installRoot() . '/themes/custom',
            static::installRoot() . '/modules/custom',
            static::installRoot() . '/profile/custom'
        ];

        foreach ($mirror_directories as $directory) {
            $path_to_directory = "{$project_root}{$directory}";
            if (!file_exists($path_to_directory)) {
                continue;
            }
            $stack->mirror($path_to_directory, "{$build_root}{$directory}");
        }
        $stack->run();

        return $this;
    }

    /**
     * Setup Drupal drush.
     *
     * The setup process consist of the following:
     *   - Copy the template drush directory into the project root.
     *   - Add drush/drush to the composer.json.
     *
     * @param bool $exclude_remote
     *   Exclude the remote drush aliases from being generated.
     *
     * @return self
     */
    public function setupDrush($exclude_remote = false)
    {
        $project_root = ProjectX::projectRoot();

        $this->taskFilesystemStack()
            ->mirror($this->getTemplateFilePath('drush'), "$project_root/drush")
            ->copy($this->getTemplateFilePath('drush.wrapper'), "$project_root/drush.wrapper")
            ->run();

        $this->taskWriteToFile("{$project_root}/drush/drushrc.php")
            ->append()
            ->place('PROJECT_ROOT', $this->getInstallRoot(true))
            ->run();

        if (!$this->hasDrush()) {
            $this->composer
                ->addDevRequire('drush/drush', static::DRUSH_VERSION);
        }

        $this->setupDrushAlias($exclude_remote);

        return $this;
    }

    /**
     * Has Drush in composer.json.
     *
     * @return bool
     */
    public function hasDrush()
    {
        return $this->hasComposerPackage('drush/drush')
            || $this->hasComposerPackage('drush/drush', true);
    }

    /**
     * Determine if drupal has configurations.
     *
     * @return bool
     */
    public function hasDrupalConfig()
    {
        $project_root = ProjectX::projectRoot();

        return $this->isDirEmpty("{$project_root}/config");
    }

    /**
     * Setup Drush aliases.
     *
     * @param bool $exclude_remote
     *   Exclude the remote drush aliases from being generated.
     *
     * @return self
     */
    public function setupDrushAlias($exclude_remote = false)
    {
        $project_root = ProjectX::projectRoot();

        if (!file_exists("$project_root/drush/site-aliases")) {
            $continue = $this->askConfirmQuestion(
                "Drush aliases haven't been setup for this project.\n"
                . "\nDo you want run the Drush setup?",
                true
            );

            if (!$continue) {
                return $this;
            }
            $this->setupDrush($exclude_remote);
        } else {
            $this->setupDrushLocalAlias();

            if (!$exclude_remote) {
                $this->setupDrushRemoteAliases();
            }
        }

        return $this;
    }

    /**
     * Setup Drupal local alias.
     *
     * The setup process consist of the following:
     *   - Create the local.aliases.drushrc.php file based on the local .
     *
     * @return self
     */
    public function setupDrushLocalAlias($alias_name = null)
    {
        $config = ProjectX::getProjectConfig();

        $alias_name = isset($alias_name)
            ? Utiltiy::machineName($alias_name)
            : ProjectX::getProjectMachineName();

        $alias_content = $this->drushAliasFileContent(
            $alias_name,
            $config->getHost()['name'],
            $this->getInstallPath()
        );
        $project_root = ProjectX::projectRoot();

        $this
            ->taskWriteToFile("$project_root/drush/site-aliases/local.aliases.drushrc.php")
            ->line($alias_content)
            ->run();

        return $this;
    }

    /**
     * Setup Drush remote aliases.
     *
     * The setup process consist of the following:
     *   - Write the remote $aliases[] into a drush alias file; which is
     *   based off the remote realm.
     *
     * @return self
     */
    public function setupDrushRemoteAliases()
    {
        $project_root = ProjectX::projectRoot();
        $drush_aliases_dir = "$project_root/drush/site-aliases";

        foreach (ProjectX::getRemoteEnvironments() as $realm => $environment) {
            $file_task = $this
                ->taskWriteToFile("$drush_aliases_dir/$realm.aliases.drushrc.php");

            $has_content = false;
            for ($i = 0; $i < count($environment); $i++) {
                $instance = $environment[$i];

                if (!isset($instance['name'])
                    || !isset($instance['path'])
                    || !isset($instance['uri'])
                    || !isset($instance['ssh_url'])) {
                    continue;
                }
                list($ssh_user, $ssh_host) = explode('@', $instance['ssh_url']);

                $options = [
                    'remote_user' => $ssh_user,
                    'remote_host' => $ssh_host
                ];

                $content = $this->drushAliasFileContent(
                    $instance['name'],
                    $instance['uri'],
                    $instance['path'],
                    $options,
                    $i === 0
                );
                $has_content = true;

                $file_task->line($content);
            }

            if ($has_content) {
                $file_task->run();
            }
        }

        return $this;
    }

    /**
     * Setup Drupal filesystem.
     *
     * The setup process consist of the following:
     *   - Change site permission.
     *   - Creates defaults files directory.
     *
     * @return self
     */
    public function setupDrupalFilesystem()
    {
        $this->taskFilesystemStack()
            ->chmod($this->sitesPath, 0775, 0000, true)
            ->mkdir("{$this->sitesPath}/default/files", 0775, true)
            ->run();

        if ($this->getProjectVersion() >= 8) {
            $install_path = $this->getInstallPath();

            $this->taskFilesystemStack()
                ->mkdir("{$install_path}/profile/custom", 0775, true)
                ->mkdir("{$install_path}/modules/custom", 0775, true)
                ->mkdir("{$install_path}/modules/contrib", 0775, true)
                ->run();
        }

        return $this;
    }

    /**
     * Setup Drupal settings file.
     *
     * The setup process consist of the following:
     *   - Copy over default settings.php.
     *   - Create the Drupal config directory.
     *   - Generate salt and place text file outside docroot.
     *   - Replace commented local.settings include statement.
     *   - Replace $config_directories with a config directory outside docroot.
     *   - Replace $settings['hash_salt'] with path to generated the salt text file.
     *
     * @return self
     */
    public function setupDrupalSettings()
    {
        $this->_copy(
            "{$this->sitesPath}/default/default.settings.php",
            $this->settingFile
        );
        $project_root = ProjectX::projectRoot();

        if ($this->getProjectVersion() >= 8) {
            $this->taskWriteToFile("{$project_root}/salt.txt")
                ->line(Utility::randomHash())
                ->run();

            $this->taskFilesystemStack()
                ->mkdir("{$project_root}/config", 0775)
                ->chmod("{$project_root}/salt.txt", 0775)
                ->run();

            $this->taskWriteToFile($this->settingFile)
                ->append()
                ->regexReplace(
                    '/\#\sif.+\/settings\.local\.php.+\n#.+\n\#\s}/',
                    $this->drupalSettingsLocalInclude()
                )
                ->replace(
                    '$config_directories = array();',
                    '$config_directories[CONFIG_SYNC_DIRECTORY] = dirname(DRUPAL_ROOT) . \'/config\';'
                )
                ->replace(
                    '$settings[\'hash_salt\'] = \'\';',
                    '$settings[\'hash_salt\'] = file_get_contents(dirname(DRUPAL_ROOT) . \'/salt.txt\');'
                )
                ->run();
        }

        return $this;
    }

    /**
     * Setup Drupal settings local include.
     *
     * @return $this
     */
    protected function setupDrupalSettingsLocalInclude()
    {
        $this->taskWriteToFile($this->settingFile)
            ->append()
            ->appendUnlessMatches(
                '/if.+\/settings\.local\.php.+{\n.+\n\}/',
                $this->drupalSettingsLocalInclude()
            )
            ->run();

        return $this;
    }

    /**
     * Setup Drupal local settings file.
     *
     * The setup process consist of the following:
     *   - Copy over example.settings.local.php.
     *   - Appends database connection details.
     *
     * @return self
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Exception
     */
    public function setupDrupalLocalSettings()
    {
        $version = $this->getProjectVersion();

        $local_settings = $this
            ->templateManager()
            ->loadTemplate("{$version}/settings.local.txt");

        $this->_remove($this->settingLocalFile);

        if ($version >= 8) {
            $setting_path = "{$this->sitesPath}/example.settings.local.php";
            if (file_exists($setting_path)) {
                $this->_copy($setting_path, $this->settingLocalFile);
            } else {
                $this->taskWriteToFile($this->settingLocalFile)
                    ->text("<?php\r\n")
                    ->run();
            }
        } else {
            $this->taskWriteToFile($this->settingLocalFile)
                ->text("<?php\r\n")
                ->run();
        }
        $database = $this->getDatabaseInfo();

        $this->taskWriteToFile($this->settingLocalFile)
            ->append()
            ->appendUnlessMatches('/\$databases\[.+\]/', $local_settings)
            ->place('DB_NAME', $database->getDatabase())
            ->place('DB_USER', $database->getUser())
            ->place('DB_PASS', $database->getPassword())
            ->place('DB_HOST', $database->getHostname())
            ->place('DB_PORT', $database->getPort())
            ->place('DB_PROTOCOL', $database->getProtocol())
            ->run();

        return $this;
    }

    /**
     * Setup Drupal install.
     *
     * The setup process consist of the following:
     *   - Check if project database is available.
     *   - Install Drupal using the drush executable.
     *   - Update install path permissions recursively.
     *
     * @param DatabaseInterface|null $database
     *   The database object.
     * @param bool $localhost
     *   A flag to determine if Drupal should be installed using localhost.
     *
     * @return self
     * @throws \Robo\Exception\TaskException
     */
    public function setupDrupalInstall(DatabaseInterface $database = null, $localhost = false)
    {
        $this->say('Waiting on Drupal database to become available...');

        $database = is_null($database)
            ? $this->getDatabaseInfo()
            : $this->getDatabaseInfoWithOverrides($database);

        $engine = $this->getEngineInstance();
        $install_path = $this->getInstallPath();

        if ($engine instanceof DockerEngineType && !$localhost) {
            $drush = $this->drushInstallCommonStack(
                '/var/www/html/vendor/bin/drush',
                '/var/www/html' . static::installRoot(),
                $database
            );
            $result = $engine->execRaw(
                $drush->getCommand(),
                $this->getPhpServiceName()
            );
        } else {
            // Run the drupal installation from the host machine. The host will
            // need to have the database client binaries installed.
            $database->setHostname('127.0.0.1');

            if (!$this->hasDatabaseConnection($database->getHostname(), $database->getPort())) {
                throw new \Exception(
                    sprintf('Unable to connection to Drupal database %s', $database->getHostname())
                );
            }
            // Sometimes it takes awhile after the mysql host is up on the
            // network to become totally available to except connections. Due to
            // the uncertainty we'll need to sleep for about 30 seconds.
            sleep(30);

            $result = $this->drushInstallCommonStack(
                'drush',
                $install_path,
                $database
            )->run();
        }
        $this->validateTaskResult($result);

        // Update permissions to ensure all files can be accessed on the install
        // path for both user and groups.
        $this->_chmod($install_path, 0775, 0000, true);

        return $this;
    }

    /**
     * Remove git submodule in vendor.
     *
     * @param null $base_path
     *   The base path on which to check for composer installed paths.
     *
     * @return $this
     */
    public function removeGitSubmodulesInVendor($base_path = null)
    {
        $base_path = isset($base_path) && file_exists($base_path)
            ? $base_path
            : ProjectX::projectRoot();

        $composer = $this->getComposer();
        $composer_config = $composer->getConfig();

        $vendor_dir = isset($composer_config['vendor-dir'])
            ? $composer_config['vendor-dir']
            : 'vendor';

        $this->removeGitSubmodules(
            [$base_path . "/{$vendor_dir}"],
            2
        );

        return $this;
    }

    /**
     * Remove git submodule in composer install path.
     *
     * @param null $base_path
     *   The base path on which to check for composer installed paths.
     *
     * @return $this
     */
    public function removeGitSubmoduleInInstallPath($base_path = null)
    {
        $this->removeGitSubmodules(
            $this->getValidComposerInstallPaths($base_path)
        );

        return $this;
    }

    /**
     * Remove .git submodules.
     *
     * @param array $search_paths
     *   An array of search paths to look for .git directories.
     *
     * @param int $depth
     *   Set directory depth for searching.
     *
     * @return $this|void
     */
    public function removeGitSubmodules(array $search_paths, $depth = 1)
    {
        if (empty($search_paths)) {
            return;
        }
        $finder = (new Finder())
            ->in($search_paths)
            ->ignoreVCS(false)
            ->ignoreDotFiles(false)
            ->depth($depth)
            ->name('.git');

        foreach ($finder as $file_info) {
            if (!$file_info->isDir()) {
                continue;
            }
            $this->_deleteDir($file_info->getPathname());
        }

        return $this;
    }

    /**
     * Get valid composer install paths.
     *
     * @param null $base_path
     *   The base path on which to check for composer installed paths.
     *
     * @return array
     */
    public function getValidComposerInstallPaths($base_path = null)
    {
        $filepaths = [];

        /** @var ComposerConfig $composer */
        $composer = $this->getComposer();
        $composer_extra = $composer->getExtra();

        $base_path = isset($base_path) && file_exists($base_path)
            ? $base_path
            : ProjectX::projectRoot();

        $installed_paths = isset($composer_extra['installer-paths'])
            ? array_keys($composer_extra['installer-paths'])
            : [];

        $installed_directory = substr(static::getInstallPath(), strrpos(static::getInstallPath(), '/'));

        foreach ($installed_paths as $installed_path) {
            $path_info = pathinfo($installed_path);
            $directory = "/{$path_info['dirname']}";
            if (strpos($directory, $installed_directory) === false) {
                continue;
            }
            $filename = $path_info['filename'] !== '{$name}'
                ? "/{$path_info['filename']}"
                : null;
            $filepath = $base_path . $directory . $filename;

            if (!file_exists($filepath)) {
                continue;
            }

            $filepaths[] = $filepath;
        }

        return $filepaths;
    }

    /**
     * Run Drush command.
     *
     * @param string|CommandBuilder $command
     *   The drush command to execute.
     * @param bool $quiet
     *   Silence command output.
     * @param bool $localhost
     *   Run command on localhost.
     *
     * @return ResultData
     */
    public function runDrushCommand($command, $quiet = false, $localhost = false)
    {
        if ($command instanceof CommandBuilder) {
            $drush = $command;
        } else {
            $drush = new DrushCommand();
            foreach (explode('&&', $command) as $string) {
                $drush->command($string);
            }
        }
        $engine = $this->getEngineInstance();

        if ($engine instanceof DockerEngineType && !$localhost) {
            $result = $engine->execRaw(
                $drush->build(),
                $this->getPhpServiceName(),
                [],
                $quiet
            );
        } else {
            $command = $drush->useLocalhost()->build();
            $execute = $this->taskExec($command);

            if ($quiet) {
                $execute->printOutput(false);
            }
            $result = $execute->run();
        }
        $this->validateTaskResult($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function rebuildSettings()
    {
        if ($this->hasDatabaseInfoChanged()) {
            $this->refreshDatabaseSettings();
        }

        return $this;
    }

    /**
     * Refresh database settings.
     *
     * @return bool
     *   Return boolean based on if refresh was successful.
     *
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function refreshDatabaseSettings()
    {
        /** @var Database $database */
        $database = $this->getDatabaseInfo();
        $settings = file_get_contents($this->settingLocalFile);

        // Replace database properties based on database info.
        foreach ($database->asArray() as $property => $value) {
            $settings = $this->replaceDatabaseProperty(
                $property,
                $value,
                $settings
            );
        }

        // Replace the namespace property based on the database protocol.
        $namespace_base = addslashes('Drupal\\\\Core\\\\Database\\\\Driver\\\\');
        $settings = $this->replaceDatabaseProperty(
            'namespace',
            "{$namespace_base}{$database->getProtocol()}",
            $settings
        );

        // Check if file contents whats updated.
        $status = file_put_contents($this->settingLocalFile, $settings);

        if (false === $status) {
            throw new \Exception(
                sprintf('Unable to refresh database settings in (%s).', $this->settingLocalFile)
            );

            return false;
        }

        return true;
    }

    /**
     * Replace database property.
     *
     * @param $property
     *   The property name.
     * @param $value
     *   The property value.
     * @param $contents
     *   The contents to search and replace the property value.
     *
     * @return string
     *   The contents with the database property value replaced.
     */
    protected function replaceDatabaseProperty($property, $value, $contents)
    {
        return preg_replace(
            "/\\'({$property})\\'\s=>\s\\'(.+)\\'\,?/",
            "'$1' => '{$value}',",
            $contents
        );
    }

    /**
     * {@inheritdoc}.
     */
    protected function buildNewProject()
    {
        $status = $this->canBuild();

        if ($status === static::BUILD_ABORT) {
            $this->say('Project build process has been aborted! ⛈️');

            return;
        }

        if ($status === static::BUILD_DIRTY) {
            $this->deleteInstallDirectory();
        }

        $this
            ->buildSteps()
            ->postBuildSteps();

        return $this;
    }

    /**
     * Drupal build existing project.
     *
     * The following setup steps are conducted:
     *   - Install composer packages.
     *   - Setup Drupal filesystem.
     *   - Setup Drupal 7/8 local settings.
     *   - Setup Drupal drush aliases.
     *   - Launch local version of project in browser.
     *
     * @param bool $engine
     *   Determine if environment engine is needed.
     * @param bool $launch_browser
     *   Launch browser to the project local domain.
     * @param null $method
     *   Set the method on which to restore the project datastore.
     * @param array $database_overrides
     *   The database overrides, only used when restoring using configs.
     * @param bool $localhost
     *   Run the commands using localhost.
     *
     * @return $this|void
     * @throws \Robo\Exception\TaskException
     */
    protected function buildExistingProject(
        $engine = true,
        $launch_browser = true,
        $method = null,
        $database_overrides = [],
        $localhost = false
    ) {
    
        $this
            ->installComposer()
            ->setupDrupalFilesystem()
            ->setupDrupalSettingsLocalInclude()
            ->setupDrupalLocalSettings()
            ->setupDrushAlias();

        if ($engine) {
            $this->projectEnvironmentUp();
        }
        $this->setupDrupalDatastore($method, $database_overrides, $localhost);

        if ($launch_browser) {
            $this->projectLaunchBrowser();
        }

        return $this;
    }

    /**
     * Clear/rebuild Drupal cache.
     *
     * @param bool $localhost
     *
     * @return ResultData
     */
    protected function clearDrupalCache($localhost = false)
    {
        $drush = new DrushCommand();

        if ($this->getProjectVersion() >= 8) {
            $drush->command('cr');
        } else {
            $drush->command('cc all');
        }

        return $this->runDrushCommand($drush, false, $localhost);
    }

    /**
     * Setup Drupal persistent datastore.
     *
     * @param null $method
     *   The method on which to restore data.
     * @param array $database_overrides
     *   The database overrides, only used when restoring using configs.
     * @param bool $localhost
     *   Run commands using the localhost.
     *
     * @return $this
     * @throws \Robo\Exception\TaskException
     */
    protected function setupDrupalDatastore(
        $method = null,
        $database_overrides = [],
        $localhost = false
    ) {
        /** @var EngineType $engine */
        $engine = $this->getEngineInstance();

        if ($engine instanceof DockerEngineType) {
            if ($this->getProjectVersion() >= 8 && $this->hasDrupalConfig()) {
                $options = ['site-config', 'database-import'];

                if (!isset($method) || !in_array($method, $options)) {
                    $method = $this->doAsk(new ChoiceQuestion(
                        'Setup the project using? ',
                        $options
                    ));
                }

                if ($method === 'site-config') {
                    $database = Database::createFromArray($database_overrides);
                    $this
                        ->setupDrupalInstall($database, $localhost)
                        ->setDrupalUuid($localhost)
                        ->importDrupalConfig(1, $localhost);
                } else {
                    $this->importDatabaseToService(null, null, $localhost);
                }
            } else {
                $this->importDatabaseToService(null, null, $localhost);
            }
            $this->clearDrupalCache($localhost);
        } else {
            $classname = get_class($engine);

            throw new \RuntimeException(
                sprintf("The engine type %s isn't supported", $classname::getLabel())
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function databaseInfoMapping()
    {
        return [
            'port' => 'port',
            'user' => 'username',
            'hostname' => 'host',
            'database' => 'database',
            'password' => 'password',
            'protocol' => 'driver',
        ];
    }

    /**
     * Determine if database info has been updated.
     *
     * @return bool
     *   Return TRUE if database info has been changed; otherwise FALSE.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function hasDatabaseInfoChanged()
    {
        $settings_uri = $this->settingLocalFile;
        if (file_exists($settings_uri)) {
            $settings = file_get_contents($settings_uri);
            $match_status = preg_match_all("/\'(database|username|password|host|port|driver)\'\s=>\s\'(.+)\'\,?/", $settings, $matches);

            if ($match_status !== false) {
                $database = $this->getDatabaseInfo();
                $database_file = array_combine($matches[1], $matches[2]);

                foreach ($database->asArray() as $property => $value) {
                    if (isset($database_file[$property])
                        && $database_file[$property] != $value) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get Drupal UUID.
     *
     * @return string
     *   The Drupal UUID.
     */
    protected function getDrupalUuid()
    {
        $uuid = null;
        $version = $this->getProjectVersion();

        if ($version >= 8) {
            $result = $this->runDrushCommand('cget system.site uuid', true);

            if ($result->getExitCode() === ResultData::EXITCODE_OK) {
                $message = $result->getMessage();

                if ($colon_pos = strrpos($message, ':')) {
                    $uuid = trim(substr($message, $colon_pos + 1));
                }
            }
        }

        return $uuid;
    }

    /**
     * Set Drupal UUID.
     *
     * @param bool $localhost
     *   Determine if the drush command should be ran on the host.
     *
     * @return $this
     * @throws \Exception
     */
    protected function setDrupalUuid($localhost = false)
    {
        if ($this->getProjectVersion() >= 8) {
            $build_info = $this->getProjectOptionByKey('build_info');

            if ($build_info !== false
                && isset($build_info['uuid'])
                && !empty($build_info['uuid'])) {
                $drush = new DrushCommand();
                $drush
                    ->command("cset system.site uuid {$build_info['uuid']}")
                    ->command('ev \'\Drupal::entityManager()->getStorage(\"shortcut_set\")->load(\"default\")->delete();\'');

                $this->runDrushCommand($drush, false, $localhost);
            }
        }

        return $this;
    }

    /**
     * Save Drupal UUID to configuration.
     *
     * @return $this
     */
    protected function saveDrupalUuid()
    {
        $config = ProjectX::getProjectConfig();
        $options[static::getTypeId()] = [
            'build_info' => [
                'uuid' => $this->getDrupalUuid()
            ]
        ];

        $config
            ->setOptions($options)
            ->save(ProjectX::getProjectPath());

        return $this;
    }

    /**
     * Drush install common command.
     *
     * @param $executable
     *   The drush executable.
     * @param $drupal_root
     *   The drupal install root.
     * @param DatabaseInterface $database
     *   The database object.
     *
     * @return DrushStack
     */
    protected function drushInstallCommonStack(
        $executable,
        $drupal_root,
        DatabaseInterface $database
    ) {
        $options = $this->getInstallOptions();

        $db_user = $database->getUser();
        $db_port = $database->getPort();
        $db_pass = $database->getPassword();
        $db_name = $database->getDatabase();
        $db_host = $database->getHostname();
        $db_protocol = $database->getProtocol();

        return $this->taskDrushStack($executable)
            ->drupalRootDirectory($drupal_root)
            ->siteName($options['site']['name'])
            ->accountMail($options['account']['mail'])
            ->accountName($options['account']['name'])
            ->accountPass($options['account']['pass'])
            ->dbUrl("$db_protocol://$db_user:$db_pass@$db_host:$db_port/$db_name")
            ->siteInstall($options['site']['profile']);
    }

    /**
     * Get Drupal install options.
     */
    protected function getInstallOptions()
    {
        return array_replace_recursive(
            $this->defaultInstallOptions(),
            $this->getProjectOptions()
        );
    }

    /**
     * Build steps to invoke during the build process.
     *
     * @return self
     */
    protected function buildSteps()
    {
        $this
            ->setupProjectComposer()
            ->setupProjectFilesystem()
            ->setupDrush();

        return $this;
    }

    /**
     * Post build steps that are invoked after the build steps.
     *
     * @return self
     */
    protected function postBuildSteps()
    {
        $this
            ->saveComposer()
            ->updateComposer();

        return $this;
    }

    /**
     * Get default Drupal install options.
     */
    protected function defaultInstallOptions()
    {
        $name = ProjectX::getProjectConfig()
            ->getName();

        return [
            'site' => [
                'name' => $name,
                'profile' => 'standard',
            ],
            'account' => [
                'mail' => 'admin@example.com',
                'name' => 'admin',
                'pass' => 'admin',
            ],
        ];
    }

    /**
     * Drupal alias PHP file content.
     *
     * @param string $name
     *   The alias name.
     * @param string $uri
     *   The drush alias URI.
     * @param string $root
     *   The root to the drupal project.
     * @param array $options
     *   An array of optional options.
     * @param boolean $include_opentag
     *   A flag to determine if the opening php tag should be rendered.
     *
     * @return string
     *   A string representation of the drush $aliases array.
     */
    protected function drushAliasFileContent($name, $uri, $root, array $options = [], $include_opentag = true)
    {
        $name = Utility::machineName($name);

        $content = $include_opentag ?  "<?php\n\n" : '';
        $content .= "\$aliases['$name'] = [";
        $content .= "\n\t'uri' => '$uri',";
        $content .= "\n\t'root' => '$root',";

        // Add remote host to output if defined.
        if (isset($options['remote_host'])
            && $remote_host = $options['remote_host']) {
            $content .= "\n\t'remote-host' => '$remote_host',";
        }

        // Add remote user to output if defined.
        if (isset($options['remote_user'])
            && $remote_user = $options['remote_user']) {
            $content .= "\n\t'remote-user' => '$remote_user',";
        }

        $content .= "\n\t'path-aliases' => [";
        $content .= "\n\t\t'%dump-dir' => '/tmp',";
        $content .= "\n\t]";
        $content .= "\n];";

        return $content;
    }

    /**
     * Set Drupal settings local include.
     *
     * @return string
     *   The php include statement.
     */
    protected function drupalSettingsLocalInclude()
    {
        if ($this->getProjectVersion() >= 8) {
            $string = 'if (file_exists("{$app_root}/{$site_path}/settings.local.php"))' . " {\n  ";
            $string .= 'include "{$app_root}/{$site_path}/settings.local.php";' . "\n}";
        } else {
            $string = 'if (file_exists(dirname(__FILE__) . "/settings.local.php"))' . " {\n  ";
            $string .= 'include dirname(__FILE__) . "/settings.local.php";' . "\n}";
        }

        return $string;
    }
}
