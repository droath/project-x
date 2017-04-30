<?php

namespace Droath\ProjectX\Command;

use Droath\ConsoleForm\Form;
use Droath\ProjectX\Config\ProjectXConfig;
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
     */
    protected function initProjectOptionForm($input, $output, $filepath)
    {
        $project = ProjectX::getProjectType();

        if ($project instanceof OptionFormAwareInterface) {
            $classname =  get_class($project);
            $label = $classname::getLabel();

            $io = new SymfonyStyle($input, $output);
            $io->title(sprintf('%s Project Options', $label));

            $form = $project->optionForm();
            $form
                ->setInput($input)
                ->setOutput($output)
                ->setHelperSet($this->getHelperSet())
                ->process();

            $options[$classname::getTypeId()] = $form->getResults();

            $saved = ProjectX::getProjectConfig()
                ->setOptions($options)
                ->save($filepath);

            if ($saved) {
                $output->writeln(
                    sprintf('🚀  <info>Success, the %s options have been saved.</info>', $label)
                );
            }
        }

        return $this;
    }
}
