<?php

namespace Droath\ProjectX\Form;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\SelectField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\FieldGroup;
use Droath\ConsoleForm\Form;
use Droath\ConsoleForm\FormInterface;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Utility;

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
                    ->setDefault($this->getDefaultName()),
                (new TextField('root', 'Project root'))
                    ->setDefault('/docroot'),
                (new SelectField('type', 'Project type'))
                    ->setOptions($this->getTypeOptions())
                    ->setDefault('drupal'),
                (new SelectField('version', 'Project version'))
                    ->setFieldCallback(function ($field, $results) {
                        $classname = $this->getTypeClassname($results['type']);
                        $field->setOptions(
                            $classname::SUPPORTED_VERSIONS
                        )
                        ->setDefault($classname::DEFAULT_VERSION);
                    }),
                (new SelectField('engine', 'Select engine'))
                    ->setOptions($this->getEngineOptions())
                    ->setDefault('docker'),
                (new BooleanField('remote', 'Setup Remote?'))
                    ->setSubform(function ($subform, $value) {
                        if ($value === true) {
                            $subform->addFields([
                                (new FieldGroup('environments'))
                                    ->addFields([
                                        (new SelectField('realm', 'Remote Realm'))
                                            ->setOptions([
                                                'stg' => 'stg',
                                                'prd' => 'prd',
                                                'dev' => 'dev',
                                            ])
                                            ->setRequired(false),
                                        (new TextField('name', 'Remote Name'))
                                            ->setCondition('realm', null, '!='),
                                        (new TextField('path', 'Remote Path'))
                                            ->setDefault('/var/www/html')
                                            ->setCondition('realm', null, '!='),
                                        (new TextField('uri', 'Remote URI'))
                                            ->setCondition('realm', null, '!='),
                                        (new TextField('ssh_url', 'Remote SSH URL'))
                                            ->setCondition('realm', null, '!='),
                                    ])
                                    ->setLoopUntil(function ($result) {
                                        if (!isset($result['realm'])) {
                                            return false;
                                        }
                                        $this->printInfoMessage('Leave remote realm empty to exit.');

                                        return true;
                                    })
                            ]);
                        }
                    }),
                (new BooleanField('github', 'Setup GitHub?'))
                    ->setSubform(function ($subform, $value) {
                        if ($value === true) {
                            $subform->addFields([
                                (new TextField('url', 'GitHub URL')),
                            ]);
                        }
                    }),
                (new BooleanField('host', 'Setup host?'))
                    ->setSubform(function ($subform, $value) {
                        if ($value === true) {
                            $subform->addFields([
                                (new TextField('name', 'Hostname'))
                                    ->setDefault($this->getDefaultHostname()),
                                (new BooleanField('open_on_startup', 'Open browser on startup?'))
                                    ->setDefault(true),
                            ]);
                        }
                    }),
            ]);
    }

    /**
     * Get project type options.
     *
     * @return array
     */
    protected function getTypeOptions()
    {
        return ProjectX::getContainer()
            ->get('projectXProjectResolver')
            ->getOptions();
    }

    /**
     * Get project type classname.
     *
     * @param string $type
     *   The project type identifier.
     *
     * @return string
     */
    protected function getTypeClassname($type)
    {
        return ProjectX::getContainer()
            ->get('projectXProjectResolver')
            ->getClassname($type);
    }

    /**
     * The current directory.
     *
     * @return string
     */
    protected function getCurrentDir()
    {
        return basename(getcwd());
    }

    /**
     * Get default name based on current directory.
     *
     * @return string
     */
    protected function getDefaultName()
    {
        $name = str_replace(
            ['-', '_'],
            ' ',
            $this->getCurrentDir()
        );

        return ucwords($name);
    }

    /**
     * Get default hostname based on current directory.
     *
     * @return string
     */
    protected function getDefaultHostname()
    {
        $hostname = Utility::machineName($this->getCurrentDir());

        return "local.{$hostname}.com";
    }

    /**
     * Get engine type options.
     *
     * @return array
     */
    protected function getEngineOptions()
    {
        return ProjectX::getContainer()
            ->get('projectXEngineResolver')
            ->getOptions();
    }

    /**
     * Print info message.
     *
     * @param string $message
     *   The message to display on the output.
     */
    protected function printInfoMessage($message)
    {
        echo "\n\033[33m[info] $message\033[0m\n\n";
    }
}
