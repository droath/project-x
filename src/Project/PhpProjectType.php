<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define PHP project type.
 */
class PhpProjectType extends ProjectType implements TaskSubTypeInterface, ProjectTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'PHP';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeId()
    {
        return 'php';
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        parent::build();

        $this
            ->useTravis()
            ->useProboCi();
    }

    /**
     * Run composer update.
     *
     * @return self
     */
    public function runComposerUpdate()
    {
        $this->taskComposerUpdate()
            ->run();

        return $this;
    }

    /**
     * Use TravisCI.
     *
     * @return self
     */
    protected function useTravis()
    {
        if ($this->askConfirmQuestion('Use TravisCI?', true)) {
            $this->copyTemplateFileToProject('.travis.yml');
        }

        return $this;
    }

    /**
     * Use ProboCI.
     *
     * @return self
     */
    protected function useProboCi()
    {
        if ($this->askConfirmQuestion('Use ProboCI?', true)) {
            $this->copyTemplateFileToProject('.probo.yml');
        }

        return $this;
    }

    /**
     * Use Behat.
     *
     * @return self
     */
    protected function useBehat()
    {
        if ($this->askConfirmQuestion('Use Behat?', true)) {
        }

        return $this;
    }

    /**
     * Composer instance.
     *
     * @return \Droath\ProjectX\Composer
     */
    protected function composer()
    {
        return $this->getContainer()->get('projectXComposer');
    }
}
