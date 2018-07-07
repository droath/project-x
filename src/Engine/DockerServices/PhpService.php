<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\ServiceInterface;

/**
 * Define the PHP docker service.
 */
class PhpService extends DockerServiceBase implements ServiceInterface
{
    /**
     * Default PHP version.
     */
    const DEFAULT_VERSION = 7.1;

    /**
     * Define docker variables.
     */
    const DOCKER_VARIABLES = [
        'PHP_PECL',
        'PHP_VERSION',
        'PHP_COMMANDS',
        'PHP_EXT_ENABLE',
        'PHP_EXT_CONFIG',
        'PHP_EXT_INSTALL',
        'PACKAGE_INSTALL',
    ];

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'php';
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        $service = (new DockerService())
            ->setBuild('./docker/services/php')
            ->setExpose(['9000'])
            ->setVolumes([
                './:/var/www/html',
                './docker/services/php/www.conf:/usr/local/etc/php-fpm.d/www.conf',
                './docker/services/php/php-overrides.ini:/usr/local/etc/php/conf.d/99-php-overrides.ini'
            ]);

        return $this->alterService($service);
    }

    public function devService()
    {
        return (new DockerService())
            ->setEnvFile([
                '.env'
            ])
            ->setEnvironment([
                'XDEBUG_CONFIG' => 'remote_host=${HOST_IP_ADDRESS}'
            ])
            ->setVolumes([
                'docker-sync:/var/www/html:nocopy'
            ]);
    }

    public function devVolumes()
    {
        return [
            'docker-sync' => [
                'external' => [
                    'name' => '${SYNC_NAME}-docker-sync'
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function templateFiles()
    {
        return [
            'Dockerfile' => [
                'variables' => $this->getDockerfileVariables(),
                'overwrite' => true,
            ],
            'www.conf' => [],
            'php-overrides.ini' => []
        ];
    }

    /**
     * Get dockerfile variables.
     *
     * @return array
     */
    protected function getDockerfileVariables()
    {
        // Merge service OS packages definition.
        if ($os_packages = $this->getInfoProperty('packages')) {
            $this->mergeConfigVariable('PACKAGE_INSTALL', $os_packages);
        }

        // Merge service PHP pecl packages definition.
        if ($pecl_packages = $this->getInfoProperty('pecl_packages')) {
            $this->mergeConfigVariable('PHP_PECL', $pecl_packages);
        }

        // Merge service PHP extensions definition.
        if ($php_extensions = $this->getInfoProperty('extensions')) {
            $this->mergeConfigVariable('PHP_EXT_INSTALL', $php_extensions);
        }

        // Merge service PHP docker command definition.
        if ($php_command = $this->getInfoProperty('commands')) {
            $this->mergeConfigVariable('PHP_COMMANDS', $php_command);
        }

        return $this->processDockerfileVariables();
    }

    /**
     * Process dockerfile variables.
     */
    protected function processDockerfileVariables()
    {
        $variables = [];
        $variables['PHP_VERSION'] = $this->getVersion();

        foreach ($this->configs as $key => $values) {
            if (!in_array($key, static::DOCKER_VARIABLES)) {
                continue;
            }

            if ($key === 'PHP_EXT_ENABLE'
                && !empty($this->configs['PHP_PECL'])) {
                $php_pecl = $this->configs['PHP_PECL'];

                // Remove the version from the PECL package.
                array_walk($php_pecl, function (&$name) {
                    $pos = strpos($name, ':');
                    if ($pos !== false) {
                        $name = substr($name, 0, $pos);
                    }
                });
                $values = array_merge($php_pecl, $values);
            }

            if (empty($values)) {
                continue;
            }

            $variables[$key] = $this->formatVariables($key, $values);
        }

        return $variables;
    }

    /**
     * Format docker variables.
     *
     * @param $key
     * @param array $values
     * @return null|string
     */
    protected function formatVariables($key, array $values)
    {
        switch ($key) {
            case 'PHP_PECL':
                return $this->formatPeclPackages($values);
            case 'PHP_COMMANDS':
                return $this->formatRunCommand($values);
            case 'PHP_EXT_CONFIG':
            case 'PHP_EXT_ENABLE':
            case 'PHP_EXT_INSTALL':
            case 'PACKAGE_INSTALL':
                return $this->formatValueDelimiter($values);
        }
    }

    /**
     * Format PECL packages.
     *
     * @param array $values
     * @return null|string
     */
    protected function formatPeclPackages(array $values)
    {
        $packages = [];

        foreach ($values as $package) {
            list($name, $version) = strpos($package, ':') !== false
                ? explode(':', $package)
                : [$package, null];

            if ($name === 'xdebug' && !isset($version)) {
                $version = version_compare($this->getVersion(), 7.0, '<')
                    ? '2.5.5'
                    : null;
            }
            $version = isset($version) ? "-{$version}" : null;
            $packages[] = "pecl install {$name}{$version} \\" ;
        }

        if (empty($packages)) {
            return null;
        }

        return "&& " . implode("\r\n  && ", $packages);
    }
}
