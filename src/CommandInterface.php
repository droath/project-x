<?php

namespace Droath\ProjectX;

interface CommandInterface
{
    /**
     * Build the command structure.
     *
     * @return string
     */
    public function build();

    /**
     * Add an executable command.
     *
     * @param $command
     *   The command for the given executable.
     *
     * @return $this
     */
    public function command($command);

    /**
     * Set command executable.
     *
     * @param $executable
     *   The command executable binary.
     *
     * @return $this
     */
    public function setExecutable($executable);

    /**
     * Set environment variable.
     *
     * @param $key
     *   The environment variable key.
     * @param $value
     *   The environment variable value.
     *
     * @return $this
     */
    public function setEnvVariable($key, $value);

    /**
     * Set command option.
     *
     * @param $option
     *   The command option key.
     * @param null|string $value
     *   The command option value.
     * @param null $delimiter
     *   The command option delimiter.
     *
     * @return $this
     */
    public function setOption($option, $value = null, $delimiter = null);
}
