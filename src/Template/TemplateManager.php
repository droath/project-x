<?php

namespace Droath\ProjectX\Template;

use Droath\ProjectX\ProjectXAwareTrait;

/**
 * Define Project-X template manager.
 */
class TemplateManager
{
    use ProjectXAwareTrait;

    /**
     * Template base directory.
     */
    const BASE_DIRECTORY = '/templates';

    /**
     * Load template file contents.
     *
     * @param string $filename
     *   The template filename.
     * @param string $format
     *   The format to use to decode the contents.
     *
     * @return array|string
     *   The decoded contents if format was found; otherwise raw content.
     */
    public function loadTemplate($filename, $format = 'json')
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
     * @return string
     *   The path to the particular template file.
     */
    public function getTemplateFilePath($filename)
    {
        $filepath = $this->getTemplatePathByProject() . "/{$filename}";

        if (!file_exists($filepath)) {
            throw new \Exception(
                'Unable to locate the template file.'
            );
        }

        return $filepath;
    }

    /**
     * Get template path by project.
     *
     * @return string
     *   The path to the template directory based on project.
     */
    public function getTemplatePathByProject()
    {
        $config = $this->getProjectXConfig();

        if (!isset($config['type'])) {
            throw new \Exception(
                'Project missing project type definition.'
            );
        }

        return  $this->templateBasePath() . '/' . $config['type'];
    }

    /**
     * Get template contents.
     *
     * @param string $filename
     *   The template filename.
     *
     * @return array
     */
    protected function getTemplateContent($filename)
    {
        $contents = file_get_contents(
            $this->getTemplateFilePath($filename)
        );

        if (!$contents) {
            return [];
        }

        return $contents;
    }

    /**
     * Project-X template base path.
     *
     * @return string
     */
    protected function templateBasePath()
    {
        return APP_ROOT . static::BASE_DIRECTORY;
    }
}
