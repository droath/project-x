<?php

namespace Droath\ProjectX\Task\Drupal;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Project\DrupalProjectType;
use Robo\Tasks;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Define Drupal specific tasks.
 */
class DrupalTasks extends Tasks
{
    /**
     * Install Drupal on the current environment.
     */
    public function drupalInstall()
    {
        $this->getProjectInstance()
            ->setupDrupalInstall();
    }

    /**
     * Setup local environment for already built projects.
     *
     * @param array $opts
     * @option bool $no-engine Don't start local development engine.
     * @option bool $no-browser Don't launch a browser window after setup is complete.
     */
    public function drupalLocalSetup($opts = ['no-engine' => false, 'no-browser' => false])
    {
        $instance = $this
            ->getProjectInstance()
            ->setupDrupalLocalSettings();

        if (!$opts['no-engine']) {
            $instance->projectEngineUp();
        }

        $instance->setupDrupalInstall();

        if (!$opts['no-browser']) {
            $instance->projectLaunchBrowser();
        }

        $this->drupalDrushAlias();

        return $this;
    }

    /**
     * Setup local project drush alias.
     */
    public function drupalDrushAlias()
    {
        $project_root = ProjectX::projectRoot();

        if (!file_exists("$project_root/drush")) {
            $continue = $this->askConfirmQuestion(
                "Drush hasn't been setup for this project.\n"
                . "\nDo you want run the Drush setup?",
                true
            );

            if (!$continue) {
                return;
            }

            $this->getProjectInstance()
                ->setupDrush();
        }

        $this->getProjectInstance()
            ->setupDrushAlias();
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
}
