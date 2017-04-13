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
        } elseif ($status === static::BUILD_DIRTY) {
            $this->deleteInstallDirectory();
        } elseif ($status === static::BUILD_FRESH) {
            $this->updateProjectComposer();
        }
        parent::build();

        if ($this->askConfirmQuestion('Use Drush?', true)) {
            $this
                ->setupDrush()
                ->setupDrushAlias();
        }

        $this
            ->setupProjectFilesystem()
            ->runComposerUpdate();
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
     * Setup Drupal drush.
     *
     * The setup process consist of the following:
     *   - Copy the template drush directory into the project root.
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
     *   The setup process consist of the following:
     *     - Change site permission.
     *     - Creates defaults files directory.
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
     *   The setup process consist of the following:
     *     - Copy over example.settings.local.php.
     *     - Appends database connection details.
     *
     * @return self
     */
    public function setupDrupalLocalSettings()
    {
        $local_settings = $this
            ->templateManager()
            ->loadTemplate('settings.local.txt', 'none');

        $this->_copy("{$this->sitesPath}/example.settings.local.php", $this->settingLocalFile);

        $this->taskWriteToFile($this->settingLocalFile)
            ->append()
            ->appendUnlessMatches('/\$databases\[.+\]/', $local_settings)
            ->run();

        return $this;
    }

    /**
     * Setup Drupal install.
     *
     *   The setup process consist of the following:
     *     - Check if project database is available.
     *     - Install Drupal using the drush executable.
     *     - Update install path permissions recursively.
     *
     * @return self
     */
    public function setupDrupalInstall()
    {
        $this->say('Waiting on engine database to become available...');

        $db_host = '127.0.0.1';
        $db_connection = $this->hasDatabaseConnection($db_host);

        if (!$db_connection) {
            throw new \Exception(
                sprintf('Unable to connection to engine database %s', $db_host)
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
            ->mysqlDbUrl("admin:root@$db_host:3306/drupal")
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
     * Get composer template file.
     *
     * @return string
     */
    protected function getComposerTemplate()
    {
        $template = 'composer.json';

        return $template;
    }

    /**
     * Update project composer.json.
     */
    protected function updateProjectComposer()
    {
        $template = $this->getComposerTemplate();
        $this->composer()
            ->mergeWithTemplate($template)
            ->update();

        return $this;
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
