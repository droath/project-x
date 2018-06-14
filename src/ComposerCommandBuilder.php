<?php

namespace Droath\ProjectX;

use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\EngineTypeInterface;
use Droath\ProjectX\Project\PhpProjectType;

/**
 * Composer command builder.
 */
class ComposerCommandBuilder extends CommandBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function findExecutable()
    {
        /** @var PhpProjectType $project */
        $project = $this->projectInstance();

        if (!$project instanceof PhpProjectType) {
            throw new \Exception(
                "Executable discovery only works for PHP based projects."
            );
        }
        $binary = parent::findExecutable();
        $composer_path =  "/vendor/bin/{$binary}";

        if (!file_exists(ProjectX::projectRoot() . $composer_path)) {
            return $binary;
        }
        /** @var EngineType $engine */
        $engine = $this->engineInstance();

        if ($engine instanceof DockerEngineType && !$this->localhost) {
            return "/var/www/html{$composer_path}";
        }

        return ProjectX::projectRoot() . $composer_path;
    }

    /**
     * Get PHP project instance.
     *
     * @return PhpProjectType
     */
    protected function projectInstance()
    {
        return ProjectX::getProjectType();
    }

    /**
     * Get engine instance.
     *
     * @return EngineTypeInterface
     */
    protected function engineInstance()
    {
        return ProjectX::getEngineType();
    }
}
