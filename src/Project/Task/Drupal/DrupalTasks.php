<?php

namespace Droath\ProjectX\Task\Drupal;

use Boedah\Robo\Task\Drush\loadTasks as drushTasks;
use Droath\ProjectX\Database;
use Droath\ProjectX\Project\Command\DrushCommand;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\DrupalProjectType;
use Droath\ProjectX\Task\EventTaskBase;
use Droath\ProjectX\TaskResultTrait;
use Droath\RoboDockerCompose\Task\loadTasks as dockerComposeTasks;
use Robo\ResultData;
use Robo\Task\Composer\loadTasks as composerTasks;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Define Drupal specific tasks.
 */
class DrupalTasks extends EventTaskBase
{
    use drushTasks;
    use composerTasks;
    use TaskResultTrait;
    use dockerComposeTasks;

    /**
     * Install Drupal on the current environment.
     *
     * @param array $opts
     *
     * @option string $db-name Set the database name.
     * @option string $db-user Set the database user.
     * @option string $db-pass Set the database password.
     * @option string $db-host Set the database host.
     * @option string $db-port Set the database port.
     * @option string $db-protocol Set the database protocol.
     * @option bool $localhost Install database using localhost.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function drupalInstall($opts = [
        'db-name' => null,
        'db-user' => null,
        'db-pass' => null,
        'db-host' => null,
        'db-port' => null,
        'db-protocol' => null,
        'localhost' => false,
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $this->getProjectInstance()
            ->setupDrupalInstall($this->buildDatabase($opts), $opts['localhost']);
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Setup local environment for already built projects.
     *
     * @param array $opts
     *
     * @option string $db-name Set the database name.
     * @option string $db-user Set the database user.
     * @option string $db-pass Set the database password.
     * @option string $db-host Set the database host.
     * @option string $db-port Set the database port.
     * @option string $db-protocol Set the database protocol.
     * @option bool $no-docker Don't use docker for local setup.
     * @option bool $no-engine Don't start local development engine.
     * @option bool $no-import Don't import Drupal configurations.
     * @option bool $no-browser Don't launch a browser window after setup is complete.
     * @option int $reimport-attempts The amount of times to retry config-import.
     * @option bool $localhost Install database using localhost.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Robo\Exception\TaskException
     */
    public function drupalLocalSetup($opts = [
        'db-name' => null,
        'db-user' => null,
        'db-pass' => null,
        'db-host' => null,
        'db-port' => null,
        'db-protocol' => null,
        'no-docker' => false,
        'no-engine' => false,
        'no-import' => false,
        'no-browser' => false,
        'reimport-attempts' => 1,
        'localhost' => false,
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $database = $this->buildDatabase($opts);

        /** @var DrupalProjectType $instance */
        $instance = $this
            ->getProjectInstance()
            ->setupDrupalFilesystem()
            ->setupDrupalLocalSettings($database);

        if (!$opts['no-engine']) {
            $instance->projectEnvironmentUp();
        }
        $localhost = $opts['localhost'];

        $drush = new DrushCommand();
        $instance->setupDrupalInstall($database, $localhost);

        $this->drupalDrushAlias();
        if ($instance->getProjectVersion() >= 8) {
            $this->setDrupalUuid($localhost);

            if (!$opts['no-import']) {
                $instance->importDrupalConfig(
                    $opts['reimport-attempts'],
                    $localhost
                );
            }
            $drush->command('cr');
        } else {
            $drush->comamnd('cc all');
        }
        $instance->runDrushCommand(
            $drush,
            false,
            $localhost
        );

        if (!$opts['no-browser']) {
            $instance->projectLaunchBrowser();
        }
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Push local environment database to remote origin (use with caution).
     */
    public function drupalRemotePush()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
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
            $drush = new DrushCommand();

            /** @var DrupalProjectType $instance */
            $instance = $this->getProjectInstance();

            if ($instance->getProjectVersion() >= 8) {
                $drush
                    ->command("sql-sync '@$local_alias' '@$remote_alias'", true)
                    ->command('cr');
            }
            $instance->runDrushCommand($drush);
        }
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Execute arbitrary drush command.
     *
     * @aliases drush
     *
     * @param array $drush_command The drush command to execute.
     * @param array $opts
     * @option bool $silent Run drush command silently.
     * @option bool $localhost Run drush command on localhost.
     *
     * @return ResultData
     * @throws \Exception
     */
    public function drupalDrush(array $drush_command, $opts = [
        'silent' => false,
        'localhost' => false,
    ])
    {
        /** @var DrupalProjectType $instance */
        $instance = $this->getProjectInstance();

        return $instance->runDrushCommand(
            implode(' ', $drush_command),
            $opts['silent'],
            $opts['localhost']
        );
    }

    /**
     * Refresh the local environment with remote data and configuration changes.
     */
    public function drupalLocalSync()
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        /** @var DrupalProjectType $instance */
        $instance = $this->getProjectInstance();

        if ($instance->getProjectVersion() >= 8) {
            $drush = new DrushCommand();

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

                $drush->command(
                    "sql-sync --sanitize --skip-tables-key='$skip_tables' '@$remote_alias' '@$local_alias'",
                    true
                );
            }

            $drush
                ->command('cim')
                ->command('updb --entity-updates')
                ->command('cr');

            $instance->runDrushCommand($drush);
        }
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Refresh the local Drupal instance.
     *
     * @param array $opts
     *
     * @option string $db-name Set the database name.
     * @option string $db-user Set the database user.
     * @option string $db-pass Set the database password.
     * @option string $db-host Set the database host.
     * @option string $db-port Set the database port.
     * @option string $db-protocol Set the database protocol.
     * @option bool $hard Refresh the site by destroying the database and rebuilding.
     * @option bool $localhost Reinstall database using localhost.
     *
     * @return self
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function drupalRefresh($opts = [
        'db-name' => null,
        'db-user' => null,
        'db-pass' => null,
        'db-host' => null,
        'db-port' => null,
        'db-protocol' => null,
        'hard' => false,
        'localhost' => false,
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $localhost = $opts['localhost'];

        /** @var DrupalProjectType $instance */
        $instance = $this->getProjectInstance();
        $version = $instance->getProjectVersion();

        // Composer install.
        $this->taskComposerInstall()->run();

        if ($opts['hard']) {
            // Reinstall the Drupal database, which drops the existing data.
            $this->getProjectInstance()
                ->setupDrupalInstall(
                    $this->buildDatabase($opts),
                    $localhost
                );

            if ($version >= 8) {
                $this->setDrupalUuid($localhost);
            }
        }
        $drush = new DrushCommand();

        if ($version >= 8) {
            $instance->runDrushCommand('updb --entity-updates');
            $instance->importDrupalConfig(1, $localhost);
            $drush->command('cr');
        } else {
            $drush
                ->command('updb')
                ->command('cc all');
        }
        $instance->runDrushCommand($drush);

        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Setup local project drush alias.
     *
     * @param array $opts
     * @option bool $exclude-remote Don't render remote drush aliases.
     *
     * @return DrupalTasks
     * @throws \Exception
     */
    public function drupalDrushAlias($opts = ['exclude-remote' => false])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        /** @var DrupalProjectType $instance */
        $instance = $this->getProjectInstance();
        $instance->setupDrushAlias($opts['exclude-remote']);
        $this->executeCommandHook(__FUNCTION__, 'after');

        return $this;
    }

    /**
     * Build database object based on options.
     *
     * @param array $options
     *   An array of options.
     *
     * @return Database
     */
    protected function buildDatabase(array $options)
    {
        return (new Database())
            ->setPort($options['db-port'])
            ->setUser($options['db-user'])
            ->setPassword($options['db-pass'])
            ->setDatabase($options['db-name'])
            ->setHostname($options['db-host'])
            ->setProtocol($options['db-protocol']);
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
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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
