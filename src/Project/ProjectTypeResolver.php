<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\Project\DrupalProjectType;
use Droath\ProjectX\Project\NullProjectType;
use Droath\ProjectX\TaskSubTypeResolver;

/**
 * Resolve project type classname.
 */
class ProjectTypeResolver extends TaskSubTypeResolver
{
    const DEFAULT_CLASSNAME = NullProjectType::class;

    /**
     * {@inheritdoc}
     */
    public function types()
    {
        return [
            DrupalProjectType::getTypeId() => DrupalProjectType::class,
        ] + $this->getPluginTypes('*ProjectType.php', 'Project');
    }
}
