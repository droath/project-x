<?php

namespace Droath\ProjectX\Project;

use Boedah\Robo\Task\Drush\loadTasks as drushTasks;
use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ProjectX\Engine\ServiceDbInterface;
use Droath\ProjectX\OptionFormAwareInterface;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubTypeInterface;
use Droath\ProjectX\Utility;

/**
 * Define Drupal project type.
 */
class DrupalProjectType extends PhpProjectType implements TaskSubTypeInterface, OptionFormAwareInterface
{
    /**
     * Composer package version constants.
     */
    const DRUSH_VERSION = '^8.1';
    const DRUPAL_8_VERSION = '^8.4';

    /**
     * Service constants.
     */
    const DEFAULT_PHP7 = 7.1;
    const DEFAULT_PHP5 = 5.6;
    const DEFAULT_MYSQL = 'latest';
    const DEFAULT_APACHE = 'latest';

    /**
     * Database constants.
     */
    const DATABASE_HOST = 'localhost';
    const DATABASE_PORT = 3306;
    const DATABASE_PROTOCOL = 'mysql';

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
                'links' => [
                    'php',
                    'database'
                ]
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
     * {@inheritdoc}.
     */
    public function build()
    {
        $status = $this->canBuild();

        if ($status === static::BUILD_ABORT) {
            $this->say('Project build process has been aborted! ⛈️');

            return;
        }

        // Remove install directory if build is dirty.
        if ($status === static::BUILD_DIRTY) {
            $this->deleteInstallDirectory();
        }
        parent::build();

        $this
            ->buildSteps()
            ->postBuildSteps();

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
            ->projectEngineUp()
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
    public function setupBehat()
    {
        parent::setupBehat();

        if ($this->hasBehat()) {
            $this->composer
                ->addDevRequire('drupal/drupal-extension', '^3.2');
        }

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
        $this->_copy(
            $this->getTemplateFilePath('.gitignore.txt'),
            ProjectX::projectRoot() . '/.gitignore',
            true
        );

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

        $this->composer
            ->setType('project')
            ->setPreferStable(true)
            ->setMinimumStability('dev')
            ->addRepository('drupal', [
                'type' => 'composer',
                'url' =>  'https://packages.drupal.org/8'
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
                'docroot/core' => ['type:drupal-core'],
                'docroot/modules/contrib/{$name}' => ['type:drupal-module'],
                'docroot/profiles/custom/{$name}' => ['type:drupal-profile'],
                'docroot/themes/contrib/{$name}'=> ['type:drupal-theme'],
                'drush/contrib/{$name}'=> ['type:drupal-drush']
            ]);

        return $this;
    }

    /**
     * Setup Drupal drush.
     *
     * The setup process consist of the following:
     *   - Copy the template drush directory into the project root.
     *   - Add drush/drush to the composer.json.
     *
     * @return self
     */
    public function setupDrush()
    {
        $project_root = ProjectX::projectRoot();

        $this->taskFilesystemStack()
            ->mirror($this->getTemplateFilePath('drush'), "$project_root/drush")
            ->copy($this->getTemplateFilePath('drush.wrapper'), "$project_root/drush.wrapper")
            ->run();

        $this->composer
            ->addDevRequire('drush/drush', static::DRUSH_VERSION);

        $this
            ->setupDrushAlias();

        return $this;
    }

    /**
     * Setup Drush aliases.
     *
     * @return self
     */
    public function setupDrushAlias($exclude_remote = false)
    {
        $this->setupDrushLocalAlias();

        if (!$exclude_remote) {
            $this->setupDrushRemoteAliases();
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

        if ($this->getProjectVersion() === 8) {
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
                $this->uncommentedIfSettingsLocal()
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

        return $this;
    }

    /**
     * Setup Drupal local settings file.
     *
     * The setup process consist of the following:
     *   - Copy over example.settings.local.php.
     *   - Appends database connection details.
     *
     * @param string $db_name
     *   The database name.
     * @param string $db_user
     *   The database username.
     * @param string $db_pass
     *   The database password.
     * @param string $db_host
     *   The database host.
     * @param bool $running_docker
     *   A flag to determine if docker is hosting the project.
     *
     * @return self
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function setupDrupalLocalSettings(
        $db_name = 'drupal',
        $db_user = 'admin',
        $db_pass = 'root',
        $db_host = '127.0.0.1',
        $running_docker = true
    ) {
        $local_settings = $this
            ->templateManager()
            ->loadTemplate('settings.local.txt');

        $this->_remove($this->settingLocalFile);
        $this->_copy("{$this->sitesPath}/example.settings.local.php", $this->settingLocalFile);

        $database = $this->getDatabaseInfo();

        if ($running_docker) {
            $db_host = $database['host'];
        }

        $this->taskWriteToFile($this->settingLocalFile)
            ->append()
            ->appendUnlessMatches('/\$databases\[.+\]/', $local_settings)
            ->place('DB_NAME', $db_name)
            ->place('DB_USER', $db_user)
            ->place('DB_PASS', $db_pass)
            ->place('DB_HOST', $db_host)
            ->place('DB_PORT', $database['port'])
            ->place('DB_PROTOCOL', $database['protocol'])
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
     * @param string $db_name The database name.
     * @param string $db_user The database username.
     * @param string $db_pass The database password.
     * @param string $db_host The database host.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Robo\Exception\TaskException
     */
    public function setupDrupalInstall(
        $db_name = 'drupal',
        $db_user = 'admin',
        $db_pass = 'root',
        $db_host = '127.0.0.1'
    ) {
        $this->say('Waiting on Drupal database to become available...');

        $database = $this->getDatabaseInfo();
        $db_port = $database['port'];
        $db_protocol = $database['protocol'];

        $db_connection = $this->hasDatabaseConnection($db_host, $db_port);

        if (!$db_connection) {
            throw new \Exception(
                sprintf('Unable to connection to Drupal database %s', $db_host)
            );
        }
        $options = $this->getInstallOptions();
        $install_path = $this->getInstallPath();

        // Sometimes it takes awhile after the mysql host is up on the network
        // to become totally available to except connections. Due to the
        // uncertainty we'll need to sleep for about 30 seconds.
        sleep(30);

        // Run Drupal site install via drush.
        $this->taskDrushStack()
            ->drupalRootDirectory($install_path)
            ->siteName($options['site']['name'])
            ->accountMail($options['account']['mail'])
            ->accountName($options['account']['name'])
            ->accountPass($options['account']['pass'])
            ->dbUrl("$db_protocol://$db_user:$db_pass@$db_host:$db_port/$db_name")
            ->siteInstall($options['site']['profile'])
            ->run();

        // Update permissions to ensure all files can be accessed on the
        // install path for both user and groups.
        $this->_chmod($install_path, 0775, 0000, true);

        return $this;
    }

    /**
     * Export Drupal configuration.
     *
     * @return self
     * @throws \Robo\Exception\TaskException
     */
    public function exportDrupalConfig()
    {
        $version = $this->getProjectVersion();

        if ($version === 8) {
            $this->taskDrushStack()
                ->drupalRootDirectory($this->getInstallPath())
                ->drush('cex')
                ->run();

            $this->saveDrupalUuid();
        }

        return $this;
    }

    /**
     * Get Project-X configuration project option by key.
     *
     * @param string $key
     *   The unique key for the option.
     *
     * @return mixed|bool
     *   The set value for the given project option key; otherwise FALSE.
     */
    public function getProjectOptionByKey($key)
    {
        $options = $this->getProjectOptions();

        if (!isset($options[$key])) {
            return false;
        }

        return $options[$key];
    }

    /**
     * Get database information based on services.
     *
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getDatabaseInfo()
    {
        $engine = $this->getEngineInstance();
        foreach ($engine->getServiceInstances() as $name => $info) {
            if (!isset($info['instance'])) {
                continue;
            }
            $instance = $info['instance'];

            if ($instance instanceof ServiceDbInterface) {
                $port = current($instance->getHostPorts());
                return [
                    'host' => $name,
                    'port' => $port,
                    'protocol' => $instance->protocol(),
                ];
            }
        }

        return [
            'host' => static::DATABASE_HOST,
            'port' => static::DATABASE_PORT,
            'protocol' => static::DATABASE_PROTOCOL,
        ];
    }

    /**
     * Get Project-X configuration project options.
     *
     * @return array
     *   An array of project options defined in the Project-X configuration.
     */
    protected function getProjectOptions()
    {
        $type_id = $this->getTypeId();
        $options = ProjectX::getProjectConfig()
            ->getOptions();

        return isset($options[$type_id])
            ? $options[$type_id]
            : [];
    }

    /**
     * Get Project-X configuration engine options.
     *
     * @return array
     *   An array of engine options defined in the project-x configuration.
     */
    protected function getEngineOptions()
    {
        $config = ProjectX::getProjectConfig();

        $engine = $config->getEngine();
        $options = $config->getOptions();

        return isset($options[$engine])
            ? $options[$engine]
            : [];
    }

    /**
     * Get Drupal UUID.
     *
     * @return string
     *   The Drupal UUID.
     * @throws \Robo\Exception\TaskException
     */
    protected function getDrupalUuid()
    {
        $uuid = null;
        $version = $this->getProjectVersion();

        if ($version === 8) {
            $result = $this->taskDrushStack()
                ->drupalRootDirectory($this->getInstallPath())
                ->drush('cget system.site uuid')
                ->run();

            $message = $result->getMessage();

            if (isset($message)) {
                $uuid = trim(
                    substr($message, strrpos($message, ':') + 1)
                );
            }
        }

        return $uuid;
    }

    /**
     * Save Drupal UUID in Project-X configuration.
     *
     * @return self
     * @throws \Robo\Exception\TaskException
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
     * The uncommented if statement for settings local.
     *
     * @return string
     *   The php statement code output.
     */
    protected function uncommentedIfSettingsLocal()
    {
        $string = 'if (file_exists("{$app_root}/{$site_path}/settings.local.php"))' . " {\n  ";
        $string .= 'include "{$app_root}/{$site_path}/settings.local.php";' . "\n}";

        return $string;
    }
}
