<?php

namespace Droath\ProjectX\Project\Command;

use Droath\ProjectX\CommandBuilder;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Project\PhpProjectType;
use Droath\ProjectX\ProjectX;

class DrushCommand extends CommandBuilder
{
    /**
     * Drush drupal docroot.
     *
     * @var string
     */
    protected $docroot;

    /**
     * Run command on localhost.
     *
     * @var bool
     */
    protected $localhost = false;

    /**
     * Run command without interaction.
     *
     * @var bool
     */
    protected $interaction = false;

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
     * Use localhost for command.
     *
     * @return $this
     */
    public function useLocalhost()
    {
        $this->localhost = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $this
            ->initBuild()
            ->setOption('-r', $this->docroot);

        if (!$this->interaction) {
            $this->setOption('yes');
        }

        return parent::build();
    }

    /**
     * Initialize build process.
     *
     * @return $this
     * @throws \Exception
     */
    protected function initBuild()
    {
        if (!isset($this->executable) && !isset($this->docroot)) {
            /** @var EngineType $engine */
            $engine = $this->engineInstance();
            /** @var DrupalProjectType $project */
            $project = $this->projectInstance();

            if (!$project instanceof PhpProjectType) {
                throw new \Exception(
                    "Command discovery only works for PHP based projects."
                );
            }
            $binary = 'drush';
            $docroot = $project->getInstallPath();

            if ($project->hasDrush()) {
                $drush_binary = '/vendor/bin/drush';
                $install_root = $project->getInstallRoot(true);

                if ($engine instanceof DockerEngineType && !$this->localhost) {
                    $base_root = "/var/www/html";
                    $binary = "{$base_root}{$drush_binary}";
                    $docroot = "{$base_root}/{$install_root}";
                } else {
                    $binary = ProjectX::projectRoot() . $drush_binary;
                }
            }

            $this
                ->setDocroot($docroot)
                ->setExecutable($binary);
        }

        return $this;
    }

    /**
     * Get project instance.
     *
     * @return ProjectTypeInterface
     */
    protected function projectInstance()
    {
        return ProjectX::getProjectType();
    }

    /**
     * Get engine instance.
     *
     * @return \Droath\ProjectX\Engine\EngineTypeInterface
     */
    protected function engineInstance()
    {
        return ProjectX::getEngineType();
    }
}
