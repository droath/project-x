<?php

namespace Droath\ProjectX\Store;

use Droath\ProjectX\Filesystem\YamlFilesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Define the abstract default YAML file store.
 */
abstract class DefaultYamlFileStore
{
    /**
     * Define the store directory.
     */
    const FILE_DIR = '.project-x';

    /**
     * Define the store filename.
     */
    const FILE_NAME = 'default.yml';

    /**
     * File path location.
     *
     * @var string
     */
    protected $filepath;

    /**
     * File contents.
     *
     * @var array
     */
    protected $contents = [];

    /**
     * File parsed data cache.
     *
     * @var array
     */
    protected $fileDataCache = [];

    /**
     * Constructor for YAML file store.
     *
     * @param string $filepath
     *   The file path to the directory where the file is stored.
     */
    public function __construct($filepath = null)
    {
        if (!isset($filepath)) {
            $filepath = $this->findFilePath();
        }

        $this->filepath = $filepath;
    }

    /**
     * Has YAML store data.
     *
     * @return bool
     */
    public function hasStoreData()
    {
        $filename = static::FILE_NAME;

        return file_exists("{$this->filepath}/{$filename}");
    }

    /**
     * Get YAML store contents.
     *
     * @return array
     *   An array of the YAML file contents.
     */
    public function getStoreData()
    {
        if (!isset($this->fileDataCache) || empty($this->fileDataCache)) {
            $data = [];

            if ($this->hasStoreData()) {
                $filename = static::FILE_NAME;
                $contents = file_get_contents("{$this->filepath}/{$filename}");

                if (!empty($contents) && $contents !== false) {
                    $data = Yaml::parse($contents);
                }
            }

            $this->fileDataCache = $data;
        }

        return $this->fileDataCache;
    }

    /**
     * Clear the data store cache.
     */
    public function clearCache()
    {
        if (isset($this->fileDataCache) && !empty($this->fileDataCache)) {
            $this->fileDataCache = null;
        }

        return $this;
    }

    /**
     * Merge current and existing contents.
     *
     * @return self
     */
    public function merge()
    {
        $this->contents = array_replace_recursive(
            $this->getStoreData(),
            $this->contents
        );

        return $this;
    }

    /**
     * Save the YAML store.
     *
     * @return int|bool
     *   The number of bytes that were written to the file, or FALSE.
     */
    public function save()
    {
        $contents = array_filter($this->contents);

        if (empty($contents)) {
            return;
        }

        if (!file_exists($this->filepath)) {
            mkdir($this->filepath);
        }

        return (new YamlFilesystem($contents, $this->filepath))
            ->save(static::FILE_NAME);
    }

    /**
     * Default file path.
     *
     * @return string
     */
    protected function defaultFilePath()
    {
        return implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'],
            static::FILE_DIR,
        ]);
    }

    /**
     * Default file locations.
     *
     * @return array
     */
    protected function defaultLocations()
    {
        $curr_dir = getcwd();
        $file_dir = static::FILE_DIR;

        return [
            "{$_SERVER['HOME']}/{$file_dir}",
            "{$curr_dir}/{$file_dir}",
        ];
    }

    /**
     * Find file path.
     *
     * @return string
     */
    protected function findFilePath()
    {
        $filename = static::FILE_NAME;

        foreach ($this->defaultLocations() as $location) {
            if (file_exists("{$location}/$filename")) {
                return $location;
            }
        }

        return $this->defaultFilePath();
    }
}
