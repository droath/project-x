<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubType;

/**
 * Define Project-X project type.
 */
abstract class EngineType extends TaskSubType implements EngineTypeInterface
{
    /**
     * Engine install path.
     */
    const INSTALL_ROOT = null;

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->say('Project engine is preparing for takeoff. 🚀');
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->say('Project engine is preparing to shutdown. 💥');
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $this->say('Project engine is preparing to start.');
    }

    /**
     * {@inheritdoc}
     */
    public function restart()
    {
        $this->say('Project engine is preparing to restart.');
    }

    /**
     * {@inheritdoc}
     */
    public function suspend()
    {
        $this->say('Project engine is preparing to suspend.');
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->say('Project engine is running the install process. 🤘');
    }

    /**
     * {@inheritdoc}
     */
    public function rebuild()
    {
        $this->say('Project engine is running the rebuild process. 🤘');
    }
    
    /**
     * Get engine install path.
     *
     * @return string
     */
    public function getInstallPath()
    {
        return ProjectX::projectRoot() . static::INSTALL_ROOT;
    }

    /**
     * Get engine options.
     *
     * @return array
     *   An array of options for the given engine.
     */
    protected function getOptions()
    {
        $engine = $this->getConfigs()->getEngine();
        $options = $this->getConfigs()->getOptions();

        return isset($options[$engine])
            ? $options[$engine]
            : [];
    }

    /**
     * Get project type.
     *
     * @return string
     *   The project type defined in the configuration.
     */
    protected function getProjectType()
    {
        return $this->getConfigs()->getType();
    }
}
