<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\EngineTrait;
use Droath\ProjectX\PlatformTrait;
use Droath\ProjectX\ProjectTrait;

/**
 * Define Project-X task base.
 */
abstract class TaskBase extends EventTaskBase
{
    use EngineTrait;
    use ProjectTrait;
    use PlatformTrait;
}
