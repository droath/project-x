<?php

namespace Droath\ProjectX;

use Droath\ProjectX\Exception\TaskResultRuntimeException;
use Robo\Result;

trait TaskResultTrait
{
    /**
     * Validate task result.
     *
     * @param Result $result
     *
     * @return Result
     * @throws TaskResultRuntimeException
     */
    protected function validateTaskResult(Result $result)
    {
        if ($result->getExitCode() !== Result::EXITCODE_OK) {
            throw new TaskResultRuntimeException($result);
        }

        return $result;
    }
}
