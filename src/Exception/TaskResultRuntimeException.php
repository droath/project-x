<?php

namespace Droath\ProjectX\Exception;

use Robo\Result;
use Throwable;

class TaskResultRuntimeException extends \RuntimeException {
    public function __construct(Result $result, Throwable $previous = NULL) {
        $task = $result->getTask();
        $code = $result->getExitCode();

        parent::__construct(
            sprintf('An exit code %d has been raised from the following task %s',
                $code,
                get_class($task)
            ),
            $code,
            $previous
        );
    }
}
