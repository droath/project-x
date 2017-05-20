<?php

namespace Droath\ProjectX\Template;

use Droath\ProjectX\ProjectX;

/**
 * Define Project-X template manager.
 */
class TemplateManager
{
    /**
     * Template project directory.
     */
    const PROJECT_DIRECTORY = '/project-x';

    /**
     * Search directories.
     *
     * @var array
     */
    protected $searchDirectories = [];

    /**
     * Load template file contents.
     *
     * @param string $filename
     *   The template filename.
     * @param string $format
     *   The format to use to decode the contents.
     *
     * @return string|array
     *   The templates raw contents; if format was provided the decoded contents
     *   is returned.
     */
    public function loadTemplate($filename, $format = null)
    {
        $contents = $this->getTemplateContent($filename);

        switch ($format) {
            case 'json':
                $contents = json_decode($contents, true);

                break;
        }

        return $contents;
    }

    /**
     * Get template file path.
     *
     * @param string $filename
     *   The file name.
     *
     * @return string|bool
     *   The path to the particular template file; otherwise false if the
     *   path doesn't exist.
     */
    public function getTemplateFilePath($filename)
    {
        $filepath = $this->locateTemplateFilePath($filename);

        if (!file_exists($filepath)) {
            return false;
        }

        return $filepath;
    }

    /**
     * Set search directory for template files.
     *
     * @param string $directory
     *   The directory on which to search for template files.
     */
    public function setSearchDirectory($directory)
    {
        $this->searchDirectories[] = $directory;

        return $this;
    }

    /**
     * Set search directories for template files.
     *
     * @param array $directories
     *   An array of directories to search for template files.
     */
    public function setSearchDirectories(array $directories)
    {
        foreach ($directories as $directory) {
            $this->setSearchDirectory($directory);
        }

        return $this;
    }

    /**
     * Has project template directory.
     *
     * @return bool
     *   Return true if the project template directory exist; otherwise false.
     */
    public function hasProjectTemplateDirectory()
    {
        return is_dir($this->templateProjectPath());
    }

    /**
     * Locate the template file path.
     *
     * It checks different template directories for a specific filename to see
     * if the template file was overridden before returning the default.
     *
     * @param string $filename
     *   The name of the template file.
     *
     * @return string
     *   The path to the template file.
     */
    protected function locateTemplateFilePath($filename)
    {
        // Search project root template directory if defined.
        if ($this->hasProjectTemplateDirectory()) {
            $filepath = $this->templateProjectPath() . "/{$filename}";

            if (file_exists($filepath)) {
                return $filepath;
            }
        }

        // Search defined directories for template files.
        foreach ($this->searchDirectories as $directory) {
            if (!file_exists($directory)) {
                continue;
            }
            $filepath = "{$directory}/{$filename}";

            if (file_exists($filepath)) {
                return $filepath;
            }
        }

        return null;
    }

    /**
     * Get template contents.
     *
     * @param string $filename
     *   The template filename.
     *
     * @return string
     *   The raw template file contents.
     */
    protected function getTemplateContent($filename)
    {
        $filepath = $this->getTemplateFilePath($filename);

        if (!$filepath) {
            return null;
        }

        $contents = file_get_contents($filepath);

        if (!$contents) {
            return null;
        }

        return $contents;
    }

    /**
     * Project-X template project path.
     *
     * @return string
     *   The project template path.
     */
    protected function templateProjectPath()
    {
        return ProjectX::projectRoot() . static::PROJECT_DIRECTORY;
    }
}
