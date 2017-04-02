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
    public function getEngine()
    {
        $config = $this->getConfig();

        return isset($config['engine']) ? $config['engine'] : null;
    }

    /**
     * Get GitHub project URL.
     *
     * @return string
     */
    public function getGitHubUrl()
    {
        $config = $this->getConfig();

        return isset($config['github']['url']) ? $config['github']['url'] : null;
    }

    /**
     * Get GitHub project URL info.
     *
     * @return array
     *   An array of account and repository values.
     */
    public function getGitHubUrlInfo()
    {
        $matches = [];
        $pattern = '/(?:https?:\/\/github.com\/|git\@.+\:)([\w\/\-\_]+)/';

        if (preg_match($pattern, $this->getGitHubUrl(), $matches)) {
            list($account, $repo) = explode(
                DIRECTORY_SEPARATOR,
                $matches[1]
            );

            return [
                'account' => $account,
                'repository' => $repo,
            ];
        }

        return [];
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
