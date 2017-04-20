<?php

namespace Droath\ProjectX\Template;

use Droath\ProjectX\ProjectX;

/**
 * Define Project-X template manager.
 */
class TemplateManager
{
    /**
     * Template base directory.
     */
    const BASE_DIRECTORY = '/templates';

    /**
     * Template project directory.
     */
    const PROJECT_DIRECTORY = '/project-x';

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
        $default = $this->getTemplatePathByProject() . "/{$filename}";

        // Check if the project has a template directory.
        if (!$this->hasProjectTemplateDirectory()) {
            return $default;
        }

        // Check if the file exist in the project template directory.
        $filepath = $this->templateProjectPath() . "/{$filename}";
        if (!file_exists($filepath)) {
            return $default;
        }

        return $filepath;
    }

    /**
     * Get template path by project.
     *
     * @return string
     *   The path to the template directory based on project.
     */
    protected function getTemplatePathByProject()
    {
        $type = ProjectX::getProjectConfig()->getType();

        if (!isset($type)) {
            throw new \Exception(
                'Project missing project type definition.'
            );
        }

        return  $this->templateBasePath() . '/' . $type;
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
     * Project-X template base path.
     *
     * @return string
     *   The application template path.
     */
    protected function templateBasePath()
    {
        return APP_ROOT . static::BASE_DIRECTORY;
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
