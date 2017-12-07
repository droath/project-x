<?php

namespace Droath\ProjectX\Task\Drupal;

use Boedah\Robo\Task\Drush\loadTasks as drushTasks;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\DrupalProjectType;
use Robo\Task\Composer\loadTasks as composerTasks;
use Robo\Tasks;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Define Drupal specific tasks.
 */
class DrupalTasks extends Tasks
{
    use drushTasks;
    use composerTasks;

    /**
     * Install Drupal on the current environment.
     *
     * @param array $opts
     * @option string $db-name Set the database name.
     * @option string $db-user Set the database user.
     * @option string $db-pass Set the database password.
     * @option string $db-host Set the database host.
     */
    public function drupalInstall($opts = [
        'db-name' => 'drupal',
        'db-user' => 'admin',
        'db-pass' => 'root',
        'db-host' => '127.0.0.1',
    ])
    {
        $this->getProjectInstance()
            ->setupDrupalInstall(
                $opts['db-name'],
                $opts['db-user'],
                $opts['db-pass'],
                $opts['db-host']
            );
    }

    /**
     * Setup local environment for already built projects.
     *
     * @param array $opts
     * @option string $db-name Set the database name.
     * @option string $db-user Set the database user.
     * @option string $db-pass Set the database password.
     * @option string $db-host Set the database host.
     * @option bool $no-docker Don't use docker for local setup.
     * @option bool $no-engine Don't start local development engine.
     * @option bool $no-import Don't import Drupal configurations.
     * @option bool $no-browser Don't launch a browser window after setup is complete.
     */
    public function drupalLocalSetup($opts = [
        'db-name' => 'drupal',
        'db-user' => 'admin',
        'db-pass' => 'root',
        'db-host' => '127.0.0.1',
        'no-docker' => false,
        'no-engine' => false,
        'no-import' => false,
        'no-browser' => false,
    ])
    {
        $db_name = $opts['db-name'];
        $db_user = $opts['db-user'];
        $db_pass = $opts['db-pass'];
        $db_host = $opts['db-host'];

        $instance = $this
            ->getProjectInstance()
            ->setupDrupalFilesystem()
            ->setupDrupalLocalSettings(
                $db_name,
                $db_user,
                $db_pass,
                $db_host,
                !$opts['no-docker'] ? true : false
            );

        if (!$opts['no-engine']) {
            $instance->projectEngineUp();
        }

        $instance->setupDrupalInstall(
            $db_name,
            $db_user,
            $db_pass,
            $db_host
        );

        if (!$opts['no-browser']) {
            $instance->projectLaunchBrowser();
        }
        $this
            ->drupalDrushAlias();

        $drush_stack = $this->getDrushStack();
        $version = $instance->getProjectVersion();

        if ($version === 8) {
            $this->setDrupalUuid();

            if (!$opts['no-import']) {
                $drush_stack
                    ->drush('cr')
                    ->drush('cim');
            }

            $drush_stack->drush('cr');
        }

        $drush_stack->run();

        return $this;
    }

    /**
     * Push local environment database to remote origin (use with caution).
     */
    public function drupalRemotePush()
    {
        $this
            ->io()
            ->warning("This command will push the local database to the remote " .
                "origin. Remote data will be destroyed. This is a dangerous " .
                "action which should be thought about for a good minute prior to " .
                "continuing. You've been warned!");

        $continue = $this->askConfirmQuestion('Shall we continue?');

        if (!$continue) {
            return $this;
        }
        $local_alias = $this->determineDrushLocalAlias();
        $remote_alias = $this->determineDrushRemoteAlias();

        if (isset($local_alias) && isset($remote_alias)) {
            $drupal = $this->getProjectInstance();
            $version = $drupal->getProjectVersion();

            if ($version === 8) {
                $this->getDrushStack()
                    ->drush("drush sql-sync '@$local_alias' '@$remote_alias'", true)
                    ->drush('cr')
                    ->run();
            }
        }

        return $this;
    }

    /**
     * Refresh the local environment with remote data and configuration changes.
     */
    public function drupalLocalSync()
    {
        $drupal = $this->getProjectInstance();
        $version = $drupal->getProjectVersion();

        if ($version === 8) {
            $drush_stack = $this->getDrushStack();

            $local_alias = $this->determineDrushLocalAlias();
            $remote_alias = $this->determineDrushRemoteAlias();

            if (isset($local_alias) && isset($remote_alias)) {
              // Drupal 8 tables to skip when syncing or dumping SQL.
                $skip_tables = implode(',', [
                    'cache_bootstrap',
                    'cache_config',
                    'cache_container',
                    'cache_data',
                    'cache_default',
                    'cache_discovery',
                    'cache_dynamic_page_cache',
                    'cache_entity',
                    'cache_menu',
                    'cache_render',
                    'history',
                    'search_index',
                    'sessions',
                    'watchdog'
                ]);

                $drush_stack->drush(
                    "drush sql-sync --sanitize --skip-tables-key='$skip_tables' '@$remote_alias' '@$local_alias'",
                    true
                );
            }

            $drush_stack
                ->drush('cim')
                ->drush('updb --entity-updates')
                ->drush('cr')
                ->run();
        }

        return $this;
    }

    /**
     * Refresh the local Drupal instance.
     *
     * @param array $opts
     * @option string $db-name Set the database name.
     * @option string $db-user Set the database user.
     * @option string $db-pass Set the database password.
     * @option string $db-host Set the database host.
     * @option bool $hard Refresh the site by destroying the database and rebuilding.
     */
    public function drupalRefresh($opts = [
        'db-name' => 'drupal',
        'db-user' => 'admin',
        'db-pass' => 'root',
        'db-host' => '127.0.0.1',
        'hard' => false,
    ])
    {
        $instance = $this->getProjectInstance();
        $version = $instance->getProjectVersion();

        // Composer install.
        $this->taskComposerInstall()->run();

        if ($opts['hard']) {
            // Reinstall the Drupal database, which drops the existing data.
            $this->getProjectInstance()
                ->setupDrupalInstall(
                    $opts['db-name'],
                    $opts['db-user'],
                    $opts['db-pass'],
                    $opts['db-host']
                );

            if ($version === 8) {
                $this->setDrupalUuid();
            }
        }
        $drush_stack = $this->getDrushStack();

        if ($version === 8) {
            $drush_stack
                ->drush('updb --entity-updates')
                ->drush('cim')
                ->drush('cr');
        } else {
            $drush_stack
                ->drush('updb')
                ->drush('cc all');
        }

        $drush_stack->run();
    }

    /**
     * Setup local project drush alias.
     *
     * @option bool $exclude-remote Don't render remote drush aliases.
     */
    public function drupalDrushAlias($opts = ['exclude-remote' => false])
    {
        $project_root = ProjectX::projectRoot();

        if (!file_exists("$project_root/drush")) {
            $continue = $this->askConfirmQuestion(
                "Drush hasn't been setup for this project.\n"
                . "\nDo you want run the Drush setup?",
                true
            );

            if (!$continue) {
                return $this;
            }

            $this->getProjectInstance()
                ->setupDrush();
        }

        $this->getProjectInstance()
            ->setupDrushAlias($opts['exclude-remote']);

        return $this;
    }

    /**
     * Get the Drush stack instance.
     *
     * @return \Boedah\Robo\Task\Drush\DrushStack
     *   The Drush stack object.
     */
    protected function getDrushStack()
    {
        $instance = $this->getProjectInstance();

        return $this->taskDrushStack()
            ->drupalRootDirectory($instance->getInstallPath());
    }

    /**
     * Set the Drupal UUID.
     */
    protected function setDrupalUuid()
    {
        $instance = $this->getProjectInstance();
        $build_info = $instance->getProjectOptionByKey('build_info');

        if ($build_info !== false && isset($build_info['uuid'])) {
            $drush_stack = $this->getDrushStack();
            $drush_stack
                ->drush("cset system.site uuid {$build_info['uuid']}")
                ->drush('ev \'\Drupal::entityManager()->getStorage("shortcut_set")->load("default")->delete();\'')
                ->run();
        }

        return $this;
    }

    /**
     * Determine Drush local alias.
     *
     * @return string
     *   The Drush local alias.
     */
    protected function determineDrushLocalAlias()
    {
        return $this->determineDrushAlias(
            'local',
            $this->getDrushAliasKeys('local')
        );
    }

    /**
     * Determine Drush remote alias.
     *
     * @return string
     *   The Drush remote alias.
     */
    protected function determineDrushRemoteAlias()
    {
        return $this->determineDrushAlias(
            'remote',
            $this->getDrushRemoteOptions()
        );
    }

    /**
     * Get Drush remote options.
     *
     * Defaults to using the dev realm to retrieve drush alias options.
     * Otherwise, the "stg" realm will be used.
     *
     * @return array
     *   An array of drush remote options.
     */
    protected function getDrushRemoteOptions()
    {
        $options = $this->getDrushAliasKeys('dev');

        return !empty($options)
            ? $options
            : $this->getDrushAliasKeys('stg');
    }

    /**
     * Get Drush alias keys.
     *
     * @param string $realm
     *   The environment realm.
     *
     * @return array
     *   An array of Drush alias keys.
     */
    protected function getDrushAliasKeys($realm)
    {
        $aliases = $this->loadDrushAliasesByRelam($realm);
        $alias_keys = array_keys($aliases);

        array_walk($alias_keys, function (&$key) use ($realm) {
            $key = "$realm.$key";
        });

        return $alias_keys;
    }

    /**
     * Determine what Drush alias to use, ask if more then one option.
     *
     * @param string $realm
     *   The environment realm
     * @param array $options
     *   An an array of options.
     *
     * @return string
     *   The Drush alias chosen.
     */
    protected function determineDrushAlias($realm, array $options)
    {
        if (count($options) > 1) {
            return $this->askChoiceQuestion(
                sprintf('Select the %s drush alias that should be used:', $realm),
                $options,
                0
            );
        }

        return reset($options) ?: null;
    }

    /**
     * Load Drush local aliases.
     *
     * @return array
     *   An array of the loaded defined alias.
     */
    protected function loadDrushAliasesByRelam($realm)
    {
        static $cached = [];

        if (empty($cached[$realm])) {
            $project_root = ProjectX::projectRoot();

            if (!file_exists("$project_root/drush")) {
                return [];
            }
            $drush_alias_dir = "$project_root/drush/site-aliases";

            if (!file_exists($drush_alias_dir)) {
                return [];
            }

            if (!file_exists("$drush_alias_dir/$realm.aliases.drushrc.php")) {
                return [];
            }

            include_once "$drush_alias_dir/$realm.aliases.drushrc.php";

            $cached[$realm] = isset($aliases) ? $aliases : array();
        }

        return $cached[$realm];
    }

    /**
     * Get the project instance.
     *
     * @return \Droath\ProjectX\Project\ProjectTypeInterface
     */
    protected function getProjectInstance()
    {
        $project = ProjectX::getProjectType();

        if (!$project instanceof DrupalProjectType) {
            throw new \Exception(
                'These tasks can only be ran for Drupal projects.'
            );
        }
        $project->setBuilder($this->getBuilder());

        return $project;
    }

    /**
     * Ask confirmation question.
     *
     * @param string $text
     *   The question text.
     * @param bool $default
     *   The default value.
     *
     * @return bool
     */
    protected function askConfirmQuestion($text, $default = false)
    {
        $default_text = $default ? 'yes' : 'no';
        $question = "☝️  $text (y/n) [$default_text] ";

        return $this->doAsk(new ConfirmationQuestion($question, $default));
    }

    /**
     * Ask choice question.
     *
     * @param string $question
     *   The question text.
     * @param array $options
     *   The question choice options.
     * @param string $default
     *   The default answer.
     *
     * @return string
     */
    protected function askChoiceQuestion($question, $options, $default = null)
    {
        return $this->doAsk(new ChoiceQuestion($question, $options, $default));
    }
}
