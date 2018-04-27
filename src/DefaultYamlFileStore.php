<?php

namespace Droath\ProjectX;

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
     * File parsed data cache.
     *
     * @var array
     */
    protected $fileDataCache = [];

    /**
     * Constructor for file store.
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
     * Has file store.
     *
     * @return bool
     */
    public function hasFile()
    {
        $filename = static::FILE_NAME;

        return file_exists("{$this->filepath}/{$filename}");
    }

    /**
     * Get file contents.
     *
     * @return array
     *   An array of the YAML file contents.
     */
    public function getFileData()
    {
        if (!isset($this->fileDataCache) || empty($this->fileDataCache)) {
            $data = [];

            if ($this->hasFile()) {
                $filename = static::FILE_NAME;
                $contents = file_get_contents("{$this->filepath}/{$filename}");

                if (!empty($contents) && $contents !== FALSE) {
                    $data = Yaml::parse($contents);
                }
            }

            $this->fileDataCache = $data;
        }

        return $this->fileDataCache;
    }

    /**
     * Clear the data store cached data.
     */
    public function clearFileDataCache()
    {
        if (isset($this->fileDataCache) && !empty($this->fileDataCache)) {
            $this->fileDataCache = null;
        }

        return $this;
    }

    /**
     * Save file to the store location.
     *
     * @param array $values
     *
     * @return int|bool
     *   The number of bytes that were written to the file, or FALSE.
     */
    public function saveFile(array $values)
    {
        if (!file_exists($this->filepath)) {
            mkdir($this->filepath);
        }

        return (new YamlFilesystem($values, $this->filepath))
            ->save(static::FILE_NAME);
    }

    /**
     * Merge file to the store location.
     *
     * @param array $values
     *
     * @return bool|int
     *   The number of bytes that were written to the file, or FALSE.
     */
    public function mergeFile(array $values)
    {
        return $this->saveFile(array_replace_recursive(
            $this->getFileData(),
            $values
        ));
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
