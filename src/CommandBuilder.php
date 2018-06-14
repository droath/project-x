<?php

namespace Droath\ProjectX;

/**
 * Define command builder base class.
 */
class CommandBuilder implements CommandInterface
{
    /**
     * Command default executable.
     */
    const DEFAULT_EXECUTABLE = null;

    /**
     * Command options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Command executable.
     *
     * @var string
     */
    protected $executable;

    /**
     * Executable commands.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Command environment variable.
     *
     * @var string
     */
    protected $envVariable = [];

    /**
     * Run command on localhost.
     *
     * @var bool
     */
    protected $localhost = false;

    /**
     * Command constructor.
     *
     * @param null $executable
     * @param bool $localhost
     */
    public function __construct($executable = null, $localhost = false)
    {
        $this->localhost = $localhost;

        if (!isset($executable)) {
            $executable = $this->findExecutable();
        }

        $this->setExecutable($executable);
    }

    /**
     * {@inheritdoc}
     */
    public function command($command)
    {
        $this->commands[] = $command;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setExecutable($executable)
    {
        $this->executable = $executable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($option, $value = null, $delimiter = null)
    {
        $delimiter = isset($delimiter) ? $delimiter : " ";

        $this->options[] = strpos($option, '-') !== false
            ? "{$option}{$delimiter}{$value}"
            : "--{$option}{$delimiter}{$value}";

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvVariable($key, $value)
    {
        $this->envVariable[] = "{$key}={$value}";

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $commands = [];

        if (empty($this->commands)) {
            $commands[] = $this->formattedCommand(null);
        } else {
            foreach ($this->commands as $command) {
                $commands[] = $this->formattedCommand($command);
            }
        }
        $this->commands = [];

        return implode(' && ', $commands);
    }

    /**
     * Get command options.
     *
     * @return string
     */
    protected function getOptions()
    {
        $options = array_map('trim', $this->options);
        return implode(' ', array_unique($options));
    }

    /**
     * Get environment variable.
     *
     * @return string
     */
    protected function getEnvVariable()
    {
        $variables = array_map('trim', $this->envVariable);
        return implode(' ;', $variables);
    }

    /**
     * Formatted command.
     *
     * @param $command
     * @return string
     */
    protected function formattedCommand($command)
    {
        $structure = [
            $this->getEnvVariable(),
            $this->executable,
            $this->getOptions(),
            $command
        ];

        return implode(' ', array_filter($structure));
    }

    /**
     * Find the executable binary.
     *
     * @return mixed
     */
    protected function findExecutable()
    {
        return static::DEFAULT_EXECUTABLE;
    }
}
