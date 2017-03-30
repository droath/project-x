<?php

namespace Droath\ProjectX;

use Symfony\Component\Yaml\Yaml;

/**
 * Project-X configuration.
 */
class Config implements ConfigInterface
{
    /**
     * Configuration local filename.
     */
    const LOCAL_FILENAME = 'project-x.local.yml';

    /**
     * Project-X path.
     *
     * @var string
     */
    protected $projectXPath;

    /**
     * Project-X configuration.
     *
     * @var array
     */
    protected $projectXConfig = [];

    /**
     * Constructor for Project-X configuration.
     *
     * @param string $path
     *   The path to the Project-X configuration file.
     */
    public function __construct($path)
    {
        $this->projectXPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $config = $this->getConfig();

        return isset($config['name']) ? $config['name'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        $config = $this->getConfig();

        return isset($config['type']) ? $config['type'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        $config = $this->getConfig();

        return isset($config['options']) ? $config['options'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        if ($this->hasConfig()
            && empty($this->projectXConfig)) {

            $config = Yaml::parse($this->getConfigContents());

            $this->projectXConfig = array_replace_recursive(
                $config,
                $this->getConfigLocal()
            );
        }

        return $this->projectXConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigLocal()
    {
        if (!$this->hasConfigLocal()) {
            return [];
        }

        return Yaml::parse(
            $this->getConfigLocalContents()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfig()
    {
        return file_exists($this->projectXPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfigLocal()
    {
        return file_exists($this->getConfigLocalPath());
    }

    /**
     * Get Project-X file contents.
     *
     * @return string
     */
    protected function getConfigContents()
    {
        return file_get_contents($this->projectXPath);
    }

    /**
     * Get Project-X local file contents.
     *
     * @return string
     */
    protected function getConfigLocalContents()
    {
        return file_get_contents($this->getConfigLocalPath());
    }

    /**
     * Get Project-X local config path.
     *
     * @return string
     */
    protected function getConfigLocalPath()
    {
        $project_root = ProjectX::projectRoot();

        return "{$project_root}/" . self::LOCAL_FILENAME;
    }
}
