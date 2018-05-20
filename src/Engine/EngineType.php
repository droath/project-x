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
        $this->say('Project is preparing for startup. 🚀');

        if (!$this->hasServices()) {
            $this->io()->note(
                'Missing service definitions in project-x configuration. This ' .
                'feature was added in version 2.2.14 to help streamline adding ' .
                'additional services without having to worry about configuration.' .
                "\n\nRun `project-x init --only-options` to generate project defaults. " .
                'After this you will be able to add/replace services to meet ' .
                'project requirements with ease.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down($include_network = false)
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
     * {@inheritdoc}
     */
    public function reboot($include_network = false)
    {
        $this->say('Project engine is rebooting.');
    }

    /**
     * {@inheritdoc}
     */
    public function ssh($service = null)
    {
        throw new \Exception(
            "Environment engine doesn't support the SSH command."
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logs($show = 'all', $follow = false, $service = null)
    {
        throw new \Exception(
            "Environment engine doesn't support the logs command."
        );
    }

    /**
     * {@inheritdoc}
     */
    public function exec($command, $service = null)
    {
        throw new \Exception(
            "Environment engine doesn't support the execute command."
        );
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
     * Get service names by type.
     *
     * @param $type
     *   The service type to search for.
     *
     * @return array
     */
    public function getServiceNamesByType($type)
    {
        if (!$this->hasServices()) {
            return [];
        }
        $types = [];
        $services = $this->getServices();

        foreach ($services as $name => $service) {
            if ($service['type'] !== $type) {
                continue;
            }
            $types[] = $name;
        }

         return $types;
    }

    /**
     * Has engine services defined.
     *
     * @return bool
     */
    public function hasServices()
    {
          return !empty($this->getServices());
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
                'instance' => static::loadService($this, $type, $name),
            ];
        }

        return $instances;
    }

    /**
     * Get service instance by type.
     *
     * @param $type
     *   The service instance type.
     *
     * @return array
     */
    public function getServiceInstanceByType($type)
    {
        $services = [];
        $instances  = $this->getServiceInstances();

        foreach ($this->getServiceNamesByType($type) as $name) {
            if (!isset($instances[$name]['instance'])) {
                continue;
            }
            $services[] = $instances[$name]['instance'];
        }

        return $services;
    }

    /**
     * Get service instance by interface.
     *
     * @param $interface
     *
     * @return bool|ServiceInterface
     *   Return the engine service; otherwise false if not found.
     */
    public function getServiceInstanceByInterface($interface)
    {
        foreach ($this->getServiceInstances() as $info) {
            if (!isset($info['instance'])) {
                continue;
            }
            $instance = $info['instance'];

            if ($instance instanceof $interface) {
                return $instance;
            }
        }

        return false;
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
     * @param EngineTypeInterface $engine
     *   The engine object.
     * @param string $type
     *   The service type.
     * @param string|null $name
     *   The service machine name.
     *
     * @return \Droath\ProjectX\Engine\ServiceInterface
     */
    public static function loadService(
        EngineTypeInterface $engine,
        $type,
        $name = null
    ) {
        $classname = static::serviceClassname($type);

        if (!class_exists($classname)) {
            throw new \RuntimeException(
                sprintf("Service class %s doesn't exist.", $classname)
            );
        }

        return new $classname($engine, $name);
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
    protected static function services()
    {
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
