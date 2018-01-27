<?php

namespace Droath\ProjectX\Command;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ProjectX\Config\ProjectXConfig;
use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\OptionFormAwareInterface;
use Droath\ProjectX\ProjectX;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Initialize extends Command
{
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
                        sprintf('🚀  <info>Success, the project-x configuration have been saved.</info>')
                    );
                    ProjectX::clearProjectConfig();
                    ProjectX::setProjectPath($filepath);
                }
            });
        }

        $this->initProjectOptionForm($input, $output, $filepath);
    }

    /**
     * Initialize the project option form.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   The console input.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   The console output.
     * @param string $filepath
     *   The project-x file path.
     *
     * @return self
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initProjectOptionForm($input, $output, $filepath)
    {
        $options = [];

        $io = new SymfonyStyle($input, $output);
        $project = ProjectX::getProjectType();

        // Add project specific options to the configuration file.
        if ($project instanceof OptionFormAwareInterface) {
            $classname = get_class($project);
            $io->title(sprintf('%s Project Options', $classname::getLabel()));

            $form = $project->optionForm();
            $form
                ->setInput($input)
                ->setOutput($output)
                ->setHelperSet($this->getHelperSet())
                ->process();

            $options[$classname::getTypeId()] = $form->getResults();
        }
        $io->newLine(2);
        $io->title('Deploy Build Options');

        $deploy_form = (new Form())
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
            ]);
        $deploy_results = $deploy_form->process()->getResults();

        if (!empty($deploy_results)) {
            $options = array_merge($options, $deploy_results);
        }
        $engine = ProjectX::getEngineType();

        // Add engine specific options to the configuration file.
        if ($engine instanceof DockerEngineType) {
            $classname = get_class($engine);
            $options[$classname::getTypeId()] = [
                'services' => $project->defaultServices()
            ];
        }

        if (!empty($options)) {
            $saved = ProjectX::getProjectConfig()
                ->setOptions($options)
                ->save($filepath);

            if ($saved) {
                $output->writeln(
                    sprintf('🚀  <info>Success, the options have been saved.</info>')
                );
            }
        }

        return $this;
    }
}
