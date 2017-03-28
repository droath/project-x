<?php

namespace Droath\ProjectX;

use Symfony\Component\Yaml\Yaml;

/**
 * Define the Project-X aware trait.
 */
trait ProjectXAwareTrait
{
    /**
     * Project-X config path.
     *
     * @var string
     */
    protected $projectXConfigPath = null;

    /**
     * {@inheritdoc}
     */
    public function setProjectXConfigPath($path)
    {
        $this->projectXConfigPath = $path;

        return $this;
    }

    /**
     * Get Project-X parse output.
     *
     * @return array
     */
    public function getProjectXConfig()
    {
        $contents = $this->getProjectXRawConfig();

        if (!$contents) {
            return [];
        }
        $configuration = Yaml::parse($contents);

        return array_replace_recursive(
            $configuration,
            $this->getProjectXLocalConfig()
        );
    }

    /**
     * Has Project-X configuration file.
     *
     * @return bool
     */
    public function hasProjectXFile()
    {
        if (!isset($this->projectXConfigPath)) {
            $this->findProjectXConfigPath();
        }

        return file_exists($this->projectXConfigPath);
    }

    /**
     * Has Project-X local configuration file.
     *
     * @return bool
     */
    public function hasProjectXLocalFile()
    {
        return file_exists($this->getProjectXLocalFilePath());
    }

    /**
     * Get Project-X file raw output.
     *
     * @return string
     */
    protected function getProjectXRawConfig()
    {
        $path = $this->findProjectXConfigPath();

        if (!isset($path)) {
            throw new \Exception(
                'Missing project-x.yml file.'
            );
        }

        return file_get_contents($path);
    }

    /**
     * Get Project-X local configurations.
     *
     * @return array
     */
    protected function getProjectXLocalConfig()
    {
        if (!$this->hasProjectXLocalFile()) {
            return [];
        }

        return Yaml::parse(
            file_get_contents($this->getProjectXLocalFilePath())
        );
    }

    /**
     * Get Project-X file root path.
     *
     * @return string|null
     */
    protected function getProjectXRootPath()
    {
        $path = $this->findProjectXConfigPath();

        return isset($path) ? dirname($path) : null;
    }

    /**
     * Get Project-X local file path.
     *
     * @return string
     *   The path to project-x local file.
     */
    protected function getProjectXLocalFilePath()
    {
        return "{$this->getProjectXRootPath()}/project-x.local.yml";
    }

    /**
     * Find Project-X file path.
     *
     * @return string
     *   The project-x path to project configuration.
     */
    protected function findProjectXConfigPath()
    {
        if (!isset($this->projectXConfigPath)) {
            $path = getcwd();

            $filename = 'project-x.yml';
            $directories = array_filter(explode('/', $path));

            $count = count($directories);
            for ($offset = 0; $offset < $count; ++$offset) {
                $next_path = '/' . implode('/', array_slice($directories, 0, $count - $offset));

                if (file_exists("{$next_path}/{$filename}")) {
                    $this->projectXConfigPath = "{$next_path}/{$filename}";
                    break;
                }
            }
        }

        return $this->projectXConfigPath;
    }
}
