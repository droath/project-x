<?php

namespace Droath\ProjectX\Command;

use Droath\ProjectX\ProjectXAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Define the Robo command.
 */
class Robo extends Command
{
    use ProjectXAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('robo')
            ->addOption(
                'classname',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the classname that should be used when generating a Robo file.',
                'RoboFile'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the path where the generated Robo file should be saved.'
            )
            ->setDescription('Generate a Robo file.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getOption('path')
            ?: $this->getDefaultPath();

        $classname = ucwords($input->getOption('classname'));
        $classpath = "{$path}/{$classname}.php";

        if (file_exists($classpath)) {
            $question = $this->getHelper('question');

            $confirm = $question->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    'Robo file already exists, do you want to overwrite it? (y/n) [no] ',
                    false
                )
            );

            if (!$confirm) {
                return;
            }
        }
        file_put_contents($classpath, $this->generateRoboClass($classname));

        $output->writeln("<info>You've successfully created a new Robo file.</info>");
    }

    /**
     * Get default path.
     *
     * @return string
     */
    protected function getDefaultPath()
    {
        return $this->hasProjectXFile()
            ? $this->getProjectXRootPath()
            : getcwd();
    }

    /**
     * Generate the Robo class.
     *
     * @param string $classname
     *   The classname.
     *
     * @return string
     *   The Robo class contents.
     */
    protected function generateRoboClass($classname)
    {
        return  '<?php'
            . "\n/**"
            . "\n * This is project's console commands configuration for Robo task runner."
            . "\n *"
            . "\n * @see http://robo.li/"
            . "\n */"
            . "\nclass " . $classname . " extends \\Robo\\Tasks\n{\n    // define public methods as commands\n}";
    }
}
