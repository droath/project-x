<?php

namespace Droath\ProjectX\Project;

use Boedah\Robo\Task\Drush\loadTasks as drushTasks;
use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ProjectX\OptionFormAwareInterface;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubTypeInterface;
use Droath\ProjectX\Utility;

/**
 * Define Drupal project type.
 */
class DrupalProjectType extends PhpProjectType implements TaskSubTypeInterface, OptionFormAwareInterface
{
    const DRUSH_VERSION = '^8.1';
    const DRUPAL_8_VERSION = '^8.3';
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
    public function getlabel()
    {
        return 'Drupal';
    }

    /**
     * {@inheritdoc}.
     */
    public function getTypeId()
    {
        return 'drupal';
    }

    /**
     * {@inheritdoc}.
     */
    public function taskDirectory()
    {
        return APP_ROOT . '/src/Project/Task/Drupal';
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
            ->setupProjectComposer()
            ->setupProjectFilesystem()
            ->askDrush()
            ->saveComposer()
            ->updateComposer();
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

        if ($this->hasBehat()) {
            $this->composer->addDevRequire('drupal/drupal-extension', '^3.2');
        }

        if ($this->hasPhpCodeSniffer()) {
            $this->composer->addDevRequire('drupal/coder', '^8.2');
        }

        return $this;
    }

    /**
     * Ask Drush to run setup.
     *
     * @return self
     */
    public function askDrush()
    {
        if ($this->askConfirmQuestion('Use Drush?', true)) {
            $this
                ->setupDrush()
                ->setupDrushAlias();
        }

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

        return $this;
    }

    /**
     * Setup Drush aliases.
     *
     * The setup process consist of the following:
     *   - Copy the Drush example template to the local drush alias
     *   configuration. Replace all placeholders with project related values.
     *
     * @return self
     */
    public function setupDrushAlias()
    {
        $config = ProjectX::getProjectConfig();
        $project_root = ProjectX::projectRoot();

        $alias_directory = "$project_root/drush/site-aliases";
        $local_alias_file = "$alias_directory/local.aliases.drushrc.php";

        $this->taskWriteToFile($local_alias_file)
            ->textFromFile("$alias_directory/local.aliases.example.drushrc.php")
            ->place('HOSTNAME', $config->getHost()['name'])
            ->place('MACHINE_NAME', ProjectX::getProjectMachineName())
            ->place('INSTALL_ROOT', $this->getInstallPath())
            ->run();

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
                '$config_directories[\'sync\'] = dirname(DRUPAL_ROOT) . \'\config\';'
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

        $this->_copy("{$this->sitesPath}/example.settings.local.php", $this->settingLocalFile);

        if ($running_docker) {
            $db_host = 'mysql';
        }

        $this->taskWriteToFile($this->settingLocalFile)
            ->append()
            ->appendUnlessMatches('/\$databases\[.+\]/', $local_settings)
            ->place('DB_NAME', $db_name)
            ->place('DB_USER', $db_user)
            ->place('DB_PASS', $db_pass)
            ->place('DB_HOST', $db_host)
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
     */
    public function setupDrupalInstall(
        $db_name = 'drupal',
        $db_user = 'admin',
        $db_pass = 'root',
        $db_host = '127.0.0.1'
    ) {
        $this->say('Waiting on Drupal database to become available...');

        $db_connection = $this->hasDatabaseConnection($db_host);

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
            ->mysqlDbUrl("$db_user:$db_pass@$db_host:3306/$db_name")
            ->siteInstall($options['site']['profile'])
            ->run();

        // Update permissions to ensure all files can be accessed on the
        // install path for both user and groups.
        $this->_chmod($install_path, 0775, 0000, true);

        return $this;
    }

    /**
     * Get Drupal install options.
     */
    protected function getInstallOptions()
    {
        $type_id = $this->getTypeId();
        $options = ProjectX::getProjectConfig()
            ->getOptions();

        $options = isset($options[$type_id])
            ? $options[$type_id]
            : [];

        return array_replace_recursive(
            $this->defaultInstallOptions(),
            $options
        );
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
