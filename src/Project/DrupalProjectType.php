<?php

namespace Droath\ProjectX\Project;

use Boedah\Robo\Task\Drush\loadTasks as drushTasks;
use Droath\ProjectX\ProjectX;

/**
 * Define Drupal project type.
 */
class DrupalProjectType extends PhpProjectType
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

        $this
            ->setupProjectFilesystem()
            ->runComposerUpdate();
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
            ->projectEngineUp()
            ->setupDrupalInstall()
            ->setupDrupalSettings()
            ->setupDrupalLocalSettings()
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
        $this->copyTemplateFileToProject('.gitignore');

        return $this;
    }

    /**
     * Setup Drupal filesystem.
     *
     *   The setup process consist of the following:
     *     - Change site permission.
     *     - Creates defaults files directory.
     *     - Copy over default settings.php
     *     - Copy over example settings.local.php.
     *
     * @return self
     */
    public function setupDrupalFilesystem()
    {
        $this->taskFilesystemStack()
            ->chmod($this->sitesPath, 0775, 0000, true)
            ->mkdir("{$this->sitesPath}/default/files", 0775, true)
            ->copy("{$this->sitesPath}/default/default.settings.php", $this->settingFile)
            ->copy("{$this->sitesPath}/example.settings.local.php", $this->settingLocalFile)
            ->run();

        return $this;
    }

    /**
     * Setup Drupal settings file.
     *
     *   The setup process consist of the following:
     *     - Appends PHP include statement for local settings.
     *
     * @return self
     */
    public function setupDrupalSettings()
    {
        $include = $this
            ->templateManager()
            ->loadTemplate('settings.txt', 'none');

        $this->taskWriteToFile($this->settingFile)
            ->append()
            ->appendUnlessMatches(
                "/(include.+\/settings.local.php\'\;)\n\}/",
                $include
            )
            ->run();

        return $this;
    }

    /**
     * Setup Drupal local settings file.
     *
     *   The setup process consist of the following:
     *     - Appends database connection details.
     *
     * @return self
     */
    public function setupDrupalLocalSettings()
    {
        $local_settings = $this
            ->templateManager()
            ->loadTemplate('settings.local.txt', 'none');

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
        $config = ProjectX::getProjectConfig()
            ->getConfig();

        $options = isset($config['options']['drupal'])
            ? $config['options']['drupal']
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

        if ($this->useBlt()) {
            $template = 'blt.composer.json';
        }

        return $template;
    }

    /**
     * Use Acquia BLT.
     */
    protected function useBlt()
    {
        return $this->askConfirmQuestion('Use Acquia BLT?', false);
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
}
