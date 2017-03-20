<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\ProjectXAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\LoadAllTasks;

/**
 * Define Project-X project type.
 */
abstract class EngineType implements BuilderAwareInterface, ContainerAwareInterface, IOAwareInterface, EngineTypeInterface
{
    use IO;
    use LoadAllTasks;
    use ContainerAwareTrait;
    use ProjectXAwareTrait;

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
     * Define engine type identifier.
     *
     * @return string
     */
    abstract public function getTypeId();
}
