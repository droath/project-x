<?php

namespace Droath\ProjectX\Project\Command;

use Droath\ProjectX\CommandBuilder;

/**
 * Define the symlink command.
 */
class SymlinkCommand extends CommandBuilder
{
    const DEFAULT_EXECUTABLE = 'ln';

    /**
     * Symlink command constructor.
     *
     * @param null $executable
     * @param bool $localhost
     */
    public function __construct($executable = null, bool $localhost = false)
    {
        parent::__construct($executable, $localhost);
        $this->setOption('-s');
    }

    /**
     * Link the source to the target.
     *
     * @param $source
     *   The source path.
     * @param $target
     *   The target path.
     *
     * @return $this
     */
    public function link($source, $target)
    {
        $this->command("$source $target");

        return $this;
    }
}
