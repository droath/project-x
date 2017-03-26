<?php

namespace Droath\ProjectX\Project;

/**
 * Define PHP project type.
 */
class PhpProjectType extends ProjectType implements ProjectTypeInterface
{
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
