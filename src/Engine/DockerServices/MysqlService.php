<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;

/**
 * Class MysqlService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class MysqlService extends DockerServiceBase implements DockerServiceInterface
{
    const DEFAULT_VERSION = 5.6;

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'mysql';
    }

    /**
     * {@inheritdoc}
     */
    public static function group()
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        $db_type = $this->dbType();

        return (new DockerService())
           ->setImage($db_type, $this->getVersion())
           ->setPorts(['3306:3306'])
           ->setVolumes([
               'mysql-data:/var/lib/mysql',
               "./docker/services/{$db_type}/mysql-overrides.cnf:/etc/mysql/mysql.conf.d/99-mysql-overrides.cnf"
           ]);
    }

    /**
     * {@inheritdoc}
     */
    public function volumes()
    {
        return [
          'mysql-data' => [
              'driver' => 'local'
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function templateFiles()
    {
        return [
            'mysql-overrides.cnf' => [],
        ];
    }

    /**
     * The database type.
     *
     * @return string
     *   The database type.
     */
    protected function dbType()
    {
        return 'mysql';
    }
}
