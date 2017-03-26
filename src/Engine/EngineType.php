<?php

namespace Droath\ProjectX\Engine;

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
     * Define engine type identifier.
     *
     * @return string
     */
    abstract public function getTypeId();

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
    public function install()
    {
        $this->say('Project engine is running the install process. 🤘');
    }

    /**
     * Get engine install path.
     *
     * @return string
     */
    public function getInstallPath()
    {
        return $this->getProjectXRootPath() . static::INSTALL_ROOT;
    }
}
