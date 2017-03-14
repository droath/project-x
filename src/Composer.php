<?php

namespace Droath\ProjectX;

use Droath\ProjectX\ComposerAwareTrait;
use Droath\ProjectX\Template\TemplateManager;

/**
 * Define the composer class.
 */
class Composer
{
    use ComposerAwareTrait;

    /**
     * Composer encoding standards.
     */
    const JSON_ENCODING = JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES;

    /**
     * Composer contents.
     *
     * @var array
     */
    protected $contents = [];

    /**
     * Template manager.
     *
     * @var \Droath\ProjectX\Template\TemplateManager
     */
    protected $templateManger;


    public function __construct(TemplateManager $template_manager)
    {
        // Gather the composer contents from the filesystem.
        $this->contents = $this->getComposerConfig();
        $this->templateManager = $template_manager;
    }

    /**
     * Get composer contents.
     *
     * @return array
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Merge composer contents with template.
     *
     * @param string $filename
     *   The filename of the template.
     *
     * @return self
     */
    public function mergeWithTemplate($filename)
    {
        $contents = $this->templateManager
            ->loadTemplate('composer/' . $filename);

        $this->contents = array_replace_recursive(
            $this->contents,
            $contents
        );

        return $this;
    }

    /**
     * Update composer file.
     *
     * @return boolean
     *   Return TRUE if composer was update successfully; otherwise FALSE.
     */
    public function update()
    {
        $config_path = $this->findComposerConfigPath();

        if (!$config_path) {
            throw new \Exception(
                'Unable to find composer configuration path.'
            );
        }
        $status = file_put_contents(
            $config_path,
            json_encode($this->contents, static::JSON_ENCODING)
        );

        return !$status ? true : false;
    }
}
