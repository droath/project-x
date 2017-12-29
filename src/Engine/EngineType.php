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
     * Get defined services in configuration.
     *
     * @return array
     *   An array of services.
     */
    public function getServices()
    {
        $options = $this->getOptions();

        return isset($options['services'])
            ? $options['services']
            : [];
    }

    /**
     * Get defined service instances.
     *
     * @return array
     *   An array of services keyed by service name.
     */
    public function getServiceInstances()
    {
        $instances = [];

        foreach ($this->getServices() as $name => $info) {
            if (!isset($info['type'])) {
                continue;
            }
            $type = $info['type'];
            unset($info['type']);

            $instances[$name] = [
                'type' => $type,
                'options' => $info,
                'instance' => static::loadService($type),
            ];
        }

        return $instances;
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
     * Load engine service.
     *
     * @param $name
     *   The service name.
     *
     * @return \Droath\ProjectX\Engine\ServiceInterface
     */
    public static function loadService($name)
    {
        $classname = static::serviceClassname($name);

        if (!class_exists($classname)) {
            throw new \RuntimeException(
                sprintf("Service class %s doesn't exist.", $classname)
            );
        }

        return new $classname();
    }

    /**
     * Get engine service classname.
     *
     * @param $name
     *   The service name.
     *
     * @return string
     *   The service fully qualified classname.
     */
    public static function serviceClassname($name)
    {
        $services = static::services();

        if (!isset($services[$name])) {
            throw new \InvalidArgumentException(
                sprintf('The provided service %s does not exist.', $name)
            );
        }

        return $services[$name];
    }

    /**
     * Define engine services class references.
     *
     * @return array
     *   An array of services referencing classname.
     */
    protected static function services() {
        return [];
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
