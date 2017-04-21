<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\Config\ComposerConfig;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define PHP project type.
 */
abstract class PhpProjectType extends ProjectType
{
    const PHPCS_VERSION = '2.*';
    const BEHAT_VERSION = '^3.1';
    const PHPUNIT_VERSION = '>=4.8.28 <5';

    /**
     * Composer instance.
     *
     * @var \Droath\ProjectX\Config\ComposerConfig
     */
    protected $composer;

    /**
     * Constructor for PHP project type.
     */
    public function __construct()
    {
        $this->composer = $this->composer();
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        parent::build();

        $this
            ->askTravisCi()
            ->askProboCi()
            ->askBehat()
            ->askPhpUnit()
            ->askPhpCodeSniffer();
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        parent::install();

        $this->initBehat();
    }

    /**
     * Ask to setup TravisCI configurations.
     *
     * @return self
     */
    public function askTravisCi()
    {
        if ($this->askConfirmQuestion('Setup TravisCI?', true)) {
            $this->setupTravisCi();
        }

        return $this;
    }

    /**
     * Setup TravisCi configurations.
     *
     * The setup steps consist of the following:
     *   - Copy .travis.yml to project root.
     */
    public function setupTravisCi()
    {
        $this->copyTemplateFileToProject('.travis.yml', true);

        return $this;
    }

    /**
     * Ask to setup ProboCI configurations.
     *
     * @return self
     */
    public function askProboCi()
    {
        if ($this->askConfirmQuestion('Setup ProboCI?', true)) {
            $this->setupProboCi();
        }

        return $this;
    }

    /**
     * Setup ProboCi configurations.
     *
     * The setup steps consist of the following:
     *   - Copy .probo.yml to project root.
     */
    public function setupProboCi()
    {
        $this->copyTemplateFileToProject('.probo.yml');

        return $this;
    }

    /**
     * Ask to setup Behat.
     *
     * @return self
     */
    public function askBehat()
    {
        if ($this->askConfirmQuestion('Setup Behat?', true)) {
            $this->setupBehat();
        }

        return $this;
    }

    /**
     * Setup Behat.
     *
     * The setup steps consist of the following:
     *   - Make tests/Behat directories in project root.
     *   - Copy behat.yml to tests/Behat directory.
     *   - Add behat package to composer instance.
     *
     * @return self
     */
    public function setupBehat()
    {
        $root_path = ProjectX::projectRoot();
        $behat_path = "{$root_path}/tests/Behat/behat.yml";

        $this->taskFilesystemStack()
            ->mkdir("{$root_path}/tests/Behat", 0775)
            ->copy($this->getTemplateFilePath('tests/behat.yml'), $behat_path)
            ->run();

        $this->composer->addRequires([
            'behat/behat' => static::BEHAT_VERSION,
        ], true);

        return $this;
    }

    /**
     * Initialize Behat for the project.
     *
     * @return self
     */
    public function initBehat()
    {
        $root_path = ProjectX::projectRoot();

        if ($this->hasBehat()
            && !file_exists("$root_path/tests/Behat/features")) {
            $this->taskBehat()
                ->option('init')
                ->option('config', "{$root_path}/tests/Behat/behat.yml")
                ->run();
        }

        return $this;
    }

    /**
     * Has Behat in composer.json.
     */
    public function hasBehat()
    {
        return $this->hasComposerPackage('behat/behat', true);
    }

    /**
     * Ask to setup PHPunit.
     *
     * @return self
     */
    public function askPhpUnit()
    {
        if ($this->askConfirmQuestion('Setup PHPUnit?', true)) {
            $this->setupPhpUnit();
        }

        return $this;
    }

    /**
     * Setup PHPunit configurations.
     */
    public function setupPhpUnit()
    {
        $root_path = ProjectX::projectRoot();

        $this->taskFilesystemStack()
            ->mkdir("{$root_path}/tests/PHPunit", 0775)
            ->copy($this->getTemplateFilePath('tests/bootstrap.php'), "{$root_path}/tests/bootstrap.php")
            ->copy($this->getTemplateFilePath('tests/phpunit.xml.dist'), "{$root_path}/phpunit.xml.dist")
            ->run();

        $this->composer->addRequires([
            'phpunit/phpunit' => static::PHPUNIT_VERSION,
        ], true);
    }

    /**
     * Has PHPunit in composer.json.
     */
    public function hasPhpUnit()
    {
        return $this->hasComposerPackage('phpunit/phpunit', true);
    }

    /**
     * Ask to setup PHP code sniffer.
     *
     * @return self
     */
    public function askPhpCodeSniffer()
    {
        if ($this->askConfirmQuestion('Setup PHP code sniffer?', true)) {
            $this->setupPhpCodeSniffer();
        }

        return $this;
    }

    /**
     * Setup PHP code sniffer.
     */
    public function setupPhpCodeSniffer()
    {
        $root_path = ProjectX::projectRoot();

        $this->taskFilesystemStack()
            ->copy($this->getTemplateFilePath('phpcs.xml.dist'), "{$root_path}/phpcs.xml.dist")
            ->run();

        $this->composer->addRequires([
            'squizlabs/php_codesniffer' => static::PHPCS_VERSION,
        ], true);
    }

    /**
     * Has PHP code sniffer in composer.json.
     *
     * @return boolean
     */
    public function hasPhpCodeSniffer()
    {
        return $this->hasComposerPackage('squizlabs/php_codesniffer', true);
    }

    /**
     * Get composer instance.
     *
     * @return \Droath\ProjectX\Config\ComposerConfig
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * Save changes to the composer.json.
     *
     * @return self
     */
    public function saveComposer()
    {
        $this->composer
            ->save($this->composerFile());

        return $this;
    }

    /**
     * Update composer packages.
     *
     * @return self
     */
    public function updateComposer()
    {
        $this->taskComposerUpdate()
            ->run();

        return $this;
    }

    /**
     * Merge project composer template.
     *
     * This will try and load a composer.json template from the project root. If
     * not found it will search in the application template root for the
     * particular project type.
     *
     * The method only exist so that projects can merge in composer requirements
     * during the project build cycle. If those requirements were declared in
     * the composer.json root, and dependencies are needed based on the project
     * type on which haven't been added yet, can cause issues.
     *
     * @return self
     */
    protected function mergeProjectComposerTemplate()
    {
        if ($contents = $this->loadTemplateContents('composer.json', 'json')) {
            $this->composer = $this->composer->update($contents);
        }

        return $this;
    }

    /**
     * Has composer package.
     *
     * @param string $vendor
     *   The package vendor project namespace.
     * @param boolean $dev
     *   A flag defining if it's a dev requirement.
     *
     * @return boolean
     */
    protected function hasComposerPackage($vendor, $dev = false)
    {
        $packages = !$dev
            ? $this->composer->getRequire()
            : $this->composer->getRequireDev();

        return isset($packages[$vendor]);
    }

    /**
     * Composer config instance.
     *
     * @return \Droath\ProjectX\Config\ComposerConfig
     */
    private function composer()
    {
        $composer_file = $this->composerFile();

        return file_exists($composer_file)
            ? ComposerConfig::createFromFile($composer_file)
            : new ComposerConfig();
    }

    /**
     * Get composer file object.
     *
     * @return \splFileInfo
     */
    private function composerFile()
    {
        return new \splFileInfo(
            ProjectX::projectRoot() . '/composer.json'
        );
    }
}
