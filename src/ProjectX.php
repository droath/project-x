<?php

namespace Droath\ProjectX;

use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

/**
 * Project-X console CLI application.
 */
class ProjectX extends Application
{
    use ProjectXAwareTrait;
    use ContainerAwareTrait;

    /**
     * Application name.
     */
    const APP_NAME = 'Project-X';

    /**
     * Application version.
     */
    const APP_VERSION = '0.0.1-alpha0';

    /**
     * {@inheritdoc}
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($this->printBanner(), static::APP_VERSION);
    }

    /**
     * Set default container services.
     */
    public static function setDefaultServices($container)
    {
        $container
            ->add('projectXComposer', \Droath\ProjectX\Composer::class)
            ->withArgument('projectXTemplate');
        $container
            ->share('projectXTemplate', \Droath\ProjectX\Template\TemplateManager::class);
        $container
            ->add('projectXHostChecker', \Droath\ProjectX\Service\HostChecker::class);
        $container
            ->share('projectXEngine', function () {
                return (new \Droath\ProjectX\Engine\EngineTypeFactory())
                    ->createInstance();
            });
        $container
            ->share('projectXProject', function () {
                return (new \Droath\ProjectX\Project\ProjectTypeFactory())
                    ->createInstance();
            });
    }

    /**
     * Get client hostname.
     *
     * @return string
     *   The client hostname.
     */
    public static function clientHostName()
    {
        return gethostname();
    }

    /**
     * Get client hostname IP.
     *
     * @return string
     *   The client hostname IP address.
     */
    public static function clientHostIP()
    {
        return getHostByName(static::clientHostName());
    }

    /**
     * Get Project-X project machine-name.
     *
     * @return string
     */
    public function getProjectMachineName()
    {
        $config = $this->getProjectXConfig();

        return strtolower(strtr($config['name'], ' ', '_'));
    }

    /**
     * Load Robo classes found in project root.
     *
     * @return array
     *   An array of Robo classes keyed by file path.
     */
    public function loadRoboProjectClasses()
    {
        $classes = [];

        foreach ($this->findPHPFilesInRoot() as $file) {
            $token_info = $this->phpFileTokenInfo($file);

            if (strpos($token_info['extends'], 'Tasks') === false) {
                continue;
            }
            $file_path = $file->getRealPath();

            // Load the Robo file so the classname is accessible, and can be
            // registered.
            if (file_exists($file_path)) {
                require_once "$file_path";
            }

            // Collect the classes that were loaded.
            $classes[$file_path] = $token_info['class'];
        }

        return $classes;
    }

    /**
     * Find PHP files in project root.
     *
     * @return \Symfony\Component\Finder\Finder
     *   An Symfony finder object.
     */
    protected function findPHPFilesInRoot()
    {
        return (new Finder())
            ->name('*.php')
            ->in($this->getProjectXRootPath())
            ->depth(0)
            ->files();
    }

    /**
     * Parse a PHP file and extract the token info.
     *
     * @param \SplFileInfo $file
     *   The file object to parse.
     *
     * @return array
     *   An array of token information that was extracted.
     */
    protected function phpFileTokenInfo(\SplFileInfo $file)
    {
        $info = [];
        $tokens = token_get_all($file->getContents());

        for ($i = 0; $i < count($tokens); ++$i) {
            $token = is_array($tokens[$i])
                ? $tokens[$i][0]
                : $tokens[$i];

            if ($token === T_CLASS) {
                $info[$tokens[$i][1]] = $this->findTokenValue(
                    $tokens,
                    [T_EXTENDS, T_INTERFACE, '{'],
                    $i
                );
                continue;
            }

            if ($token === T_EXTENDS) {
                $info[$tokens[$i][1]] = $this->findTokenValue(
                    $tokens,
                    ['{'],
                    $i
                );
                continue;
            }
        }

        return $info;
    }

    /**
     * Find PHP token value.
     *
     * @param array $tokens
     *   An array of PHP tokens.
     * @param array $endings
     *   An array of endings that should be searched.
     * @param int $iteration
     *   The token iteration count.
     * @param bool $skip_whitespace
     *   A flag to determine if whitespace should be skipped.
     *
     * @return string
     *   The PHP token content value.
     */
    protected function findTokenValue(array $tokens, array $endings, $iteration, $skip_whitespace = true)
    {
        $value = null;
        $count = count($tokens);

        for ($i = $iteration + 1; $i < $count; ++$i) {
            $token = is_array($tokens[$i])
                ? $tokens[$i][0]
                : $tokens[$i];

            if ($token === T_WHITESPACE
                && $skip_whitespace) {
                continue;
            }

            if (in_array($token, $endings)) {
                break;
            }

            $value .= $tokens[$i][1];
        }

        return $value;
    }

    /**
     * Print application banner.
     */
    private function printBanner()
    {
        $filename = dirname(__DIR__) . '/banner.txt';

        if (!file_exists($filename)) {
            return static::APP_NAME;
        }

        return file_get_contents($filename);
    }
}
