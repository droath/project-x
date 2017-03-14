<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\ProjectXAwareTrait;
use Robo\Tasks;

/**
 * Define Project-X task base.
 */
abstract class TaskBase extends Tasks
{
    use ProjectXAwareTrait;
}
