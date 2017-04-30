<?php

namespace Droath\ProjectX;

use Droath\ProjectX\Discovery\PhpClassDiscovery;
use Droath\ProjectX\ProjectX;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Task subtype resolver.
 */
abstract class TaskSubTypeResolver
{
    /**
     * Cache adapter.
     *
     * @var \Symfony\Component\Cache\Adapter\AdapterInterface
     */
    protected $cache;

    /**
     * The default classname.
     */
    const DEFAULT_CLASSNAME = null;

    /**
     * The task subtype resolver constructor.
     *
     * @param \Symfony\Component\Cache\Adapter\AdapterInterface $cache_backend
     *   The cache backend adapter.
     */
    public function __construct(AdapterInterface $cache_backend)
    {
        $this->cache = $cache_backend;
    }

    /**
     * Define task subtype types.
     *
     * @return array
     *   An array of task subtype types.
     */
    abstract public function types();

    /**
     * Get available types options.
     *
     * @return array
     *   An array of type options.
     */
    public function getOptions()
    {
        $types = $this->types();

        array_walk($types, function (&$classname) {
            $classname = $classname::getLabel();
        });

        return $types;
    }

    /**
     * Get classname by type.
     *
     * @return string|null
     *   The resolved classname; otherwise the default classname is returned.
     */
    public function getClassname($type)
    {
        $types = $this->types();

        if (isset($types[$type])) {
            return $types[$type];
        }

        return static::DEFAULT_CLASSNAME;
    }

    /**
     * Get plugin types.
     *
     * @param string $pattern
     *   The plugin type search pattern.
     * @param string $plugin_path
     *   The plugin path after the /src directory.
     *
     * @return array
     *   An array of plugin type class names, keyed by type identifier.
     */
    protected function getPluginTypes($pattern, $plugin_path)
    {
        $types = [];
        $project_root = ProjectX::projectRoot();

        foreach ($this->getInstalledPluginNamespaces() as $name => $namespace) {
            $plugin_dir = "$project_root/vendor/$name";

            if (!file_exists($plugin_dir)) {
                continue;
            }
            $plugin_dir = "$plugin_dir/src";

            if (isset($plugin_path)) {
                $plugin_dir = "$plugin_dir/$plugin_path";
            }

            $classes = (new PhpClassDiscovery)
                ->setSearchPattern($pattern)
                ->addSearchLocation($plugin_dir)
                ->discover();

            foreach ($classes as $classname) {
                if (!class_exists($classname)) {
                    continue;
                }

                $types[$classname::getTypeId()] = $classname;
            }
        }

        return $types;
    }

    /**
     * Get composer installed plugin namespaces.
     *
     * @return array
     *   An array of installed plugin PSR-4 classnames, keyed by the project
     *   name.
     */
    protected function getInstalledPluginNamespaces()
    {
        $cache_item = $this->cache->getItem('plugins.installed');

        $project_root = ProjectX::projectRoot();
        $installed_file = "$project_root/vendor/composer/installed.json";

        if (!$cache_item->isHit()
            && file_exists($installed_file)) {
            $installed = json_decode(
                file_get_contents($installed_file),
                true
            );
            $namespaces = [];

            foreach ($installed as $info) {
                if (empty($info['autoload']) ||
                    $info['type'] !== 'project-x') {
                    continue;
                }

                if (isset($info['autoload']['psr-4'])) {
                    $namespaces[$info['name']] = key($info['autoload']['psr-4']);
                }
            }

            if (!empty($namespaces)) {
                $cache_item
                    ->set($namespaces)
                    ->expiresAfter(3600);

                $this->cache->save($cache_item);
            }
        }

        return $cache_item->get() ?: [];
    }
}
