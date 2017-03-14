<?php

namespace Droath\ProjectX\Form;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\SelectField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ConsoleForm\FormInterface;

/**
 * Define Project-X setup form.
 */
class ProjectXSetup implements FormInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'project-x.form.setup';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm()
    {
        return (new Form())
            ->addFields([
                (new TextField('name', 'Project name'))
                    ->setDefault('Project-X'),
                (new SelectField('type', 'Project type'))
                    ->setOptions($this->getProjectTypes())
                    ->setDefault('drupal'),
                (new SelectField('engine', 'Select engine'))
                    ->setOptions($this->getEngines())
                    ->setDefault('docker'),
                (new BooleanField('host', 'Setup host?'))
                    ->setSubform(function ($subform, $value) {
                        if ($value === true) {
                            $subform->addFields([
                                (new TextField('name', 'Hostname'))
                                    ->setDefault('local.project-x.com'),
                                (new BooleanField('open_on_startup', 'Open browser on startup?'))
                                    ->setDefault(true),
                            ]);
                        }
                    }),
            ]);
    }

    protected function getProjectTypes()
    {
        return [
            'drupal' => 'Drupal',
            'php' => 'PHP',
        ];
    }

    /**
     * Get available engines.
     *
     * @return array
     */
    protected function getEngines()
    {
        return [
            'docker' => 'Docker',
        ];
    }

}
