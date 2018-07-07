<?php

namespace Droath\ProjectX\Command;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ProjectX\Config\ProjectXConfig;
use Droath\ProjectX\DeployAwareInterface;
use Droath\ProjectX\Engine\EngineServiceInterface;
use Droath\ProjectX\OptionFormAwareInterface;
use Droath\ProjectX\Platform\NullPlatformType;
use Droath\ProjectX\ProjectX;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Initialize extends Command
{
    /**
     * Project-x option configurations.
     *
     * @var array
     */
    protected $options = [];

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Generate Project-X configuration.')
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set the path for the Project-X configuration.',
                getcwd()
            )
            ->addOption(
                'only-options',
                null,
                InputOption::VALUE_NONE,
                'Only generate options related to Project-X configurations.'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $form = $this
            ->getHelper('form')
            ->getFormByName('project-x.form.setup', $input, $output);

        $path = $input->getOption('path');

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(
                'Path does not exist.'
            );
        }
        $filename = 'project-x.yml';
        $filepath = "{$path}/{$filename}";

        if (!$input->getOption('only-options')) {
            $form->save(function ($results) use ($output, $filepath) {
                $saved = ProjectXConfig::createFromArray($results)
                    ->save($filepath);

                if ($saved) {
                    $output->writeln(
                        sprintf('🚀  <info>Success, the project-x configurations have been saved.</info>')
                    );
                    ProjectX::clearProjectConfig();
                    ProjectX::setProjectPath($filepath);
                }
            });
        }
        $this
            ->setPlatformOptions($input, $output)
            ->setProjectOptions($input, $output)
            ->setDeployOptions($input, $output)
            ->setEngineServiceOptions();

        if (!empty($this->options)) {
            $saved = ProjectX::getProjectConfig()
                ->setOptions($this->options)
                ->save($filepath);

            if ($saved) {
                $output->writeln(
                    sprintf('🚀  <info>Success, the project-x options have been saved.</info>')
                );
            }
        }
    }

    /**
     * Set project platform options.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return Initialize
     */
    protected function setPlatformOptions(InputInterface $input, OutputInterface $output)
    {
        $platform = ProjectX::getPlatformType();

        if (!$platform instanceof NullPlatformType
            && $platform instanceof OptionFormAwareInterface) {
            $classname = get_class($platform);
            $command_io = new SymfonyStyle($input, $output);
            $command_io->newLine(2);
            $command_io->title(sprintf('%s Platform Options', $classname::getLabel()));

            $form = $platform->optionForm();
            $form
                ->setInput($input)
                ->setOutput($output)
                ->setHelperSet($this->getHelperSet())
                ->process();

            $this->options[$classname::getTypeId()] = $form->getResults();
        }

        return $this;
    }

    /**
     * Set project options.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return $this
     */
    protected function setProjectOptions(InputInterface $input, OutputInterface $output)
    {
        $project = ProjectX::getProjectType();

        if ($project instanceof OptionFormAwareInterface) {
            $classname = get_class($project);

            $command_io = new SymfonyStyle($input, $output);
            $command_io->newLine(2);
            $command_io->title(sprintf('%s Project Options', $classname::getLabel()));

            $form = $project->optionForm();
            $form
                ->setInput($input)
                ->setOutput($output)
                ->setHelperSet($this->getHelperSet())
                ->process();

            $this->options[$classname::getTypeId()] = $form->getResults();
        }

        return $this;
    }

    /**
     * Set project deployment options.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return $this
     */
    protected function setDeployOptions(InputInterface $input, OutputInterface $output)
    {
        $project = ProjectX::getProjectType();

        if ($project instanceof DeployAwareInterface) {
            $command_io = new SymfonyStyle($input, $output);
            $command_io->title('Deploy Build Options');

            $form = (new Form())
                ->setInput($input)
                ->setOutput($output)
                ->setHelperSet($this->getHelperSet())
                ->addFields([
                    (new BooleanField('deploy', 'Setup build deploy?'))
                        ->setDefault(false)
                        ->setSubform(function ($subform, $value) {
                            if (true === $value) {
                                $subform->addFields([
                                    (new TextField('github_repo', 'GitHub Repo')),
                                ]);
                            }
                        })
                ])
                ->process();

            $results = $form->getResults();

            if (isset($results['deploy']) && !empty($results['deploy'])) {
                $this->options['deploy'] = $results['deploy'];
            }
        }

        return $this;
    }

    /**
     * Set the project engine services options.
     */
    protected function setEngineServiceOptions()
    {
        $project = ProjectX::getProjectType();

        if ($project instanceof EngineServiceInterface) {
            $engine = ProjectX::getEngineType();

            $classname = get_class($engine);
            $this->options[$classname::getTypeId()] = [
                'services' => $project->defaultServices()
            ];
        }

        return $this;
    }
}
