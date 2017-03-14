<?php

namespace Droath\ProjectX;

use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Application;

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
    public function setDefaultServices()
    {
        $this->container
            ->add('projectXComposer', \Droath\ProjectX\Composer::class)
            ->withArgument('projectXTemplate');
        $this->container
            ->share('projectXTemplate', \Droath\ProjectX\Template\TemplateManager::class);
        $this->container
            ->add('projectXHostChecker', \Droath\ProjectX\Service\HostChecker::class);
        $this->container
            ->share('projectXEngine', function () {
                return (new \Droath\ProjectX\Engine\EngineTypeFactory())
                    ->createInstance();
            });
        $this->container
            ->share('projectXProject', function () {
                return (new \Droath\ProjectX\Project\ProjectTypeFactory())
                    ->createInstance();
            });
    }

    /**
     * Has project-x configuration.
     *
     * @return bool
     */
    public function hasProjectXConfig()
    {
        return $this->findProjectXConfigPath() !== false;
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
