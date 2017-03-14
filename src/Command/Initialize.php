<?php

namespace Droath\ProjectX\Command;

use Droath\ConsoleForm\Form;
use Droath\ProjectX\Filesystem\YamlFilesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Initialize extends Command
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Generate Project-X configuration.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $form = $this
            ->getHelper('form')
            ->getFormByName('project-x.form.setup', $input, $output);

        $form->save(function ($results) use ($input, $output) {
            $filename = 'project-x.yml';
            $saved = (new YamlFilesystem($results))
                ->save($filename);

            if ($saved) {
                $output->writeln(
                    sprintf('🚀  <info>Success, the %s has been generated!</info>', $filename)
                );
            }
        });
    }
}
