<?php

namespace Droath\ProjectX\Project\Command;

use Droath\ProjectX\ComposerCommandBuilder;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Project\DrupalProjectType;

class DrushCommand extends ComposerCommandBuilder
{
    const DEFAULT_EXECUTABLE = 'drush';

    /**
     * Drush drupal docroot.
     *
     * @var string
     */
    protected $docroot;

    /**
     * Run command without interaction.
     *
     * @var bool
     */
    protected $interaction = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($executable = null, $localhost = false, $docroot = null)
    {
        parent::__construct($executable, $localhost);

        if (!isset($docroot)) {
            $docroot = $this->findDrupalDocroot();
        }

        $this->setDocroot($docroot);
    }

    /**
     * Drush set docroot.
     *
     * @param $docroot
     *   The path to the drupal root.
     *
     * @return $this
     */
    public function setDocroot($docroot)
    {
        $this->docroot = $docroot;

        return $this;
    }

    /**
     * Enable Drush interaction.
     *
     * @return $this
     */
    public function enableInteraction()
    {
        $this->interaction = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $this->setOption('-r', $this->docroot);

        if (!$this->interaction) {
            $this->setOption('yes');
        }

        return parent::build();
    }

    /**
     * Find Drupal docroot.
     *
     * @return string
     */
    protected function findDrupalDocroot()
    {
        /** @var EngineType $engine */
        $engine = $this->engineInstance();

        /** @var DrupalProjectType $project */
        $project = $this->projectInstance();

        if ($engine instanceof DockerEngineType && !$this->localhost) {
            return "/var/www/html/{$project->getInstallRoot(true)}";
        }

        return $project->getInstallPath();
    }
}
