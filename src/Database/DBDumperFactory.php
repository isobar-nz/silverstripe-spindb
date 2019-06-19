<?php

namespace LittleGiant\SpinDB\Database;

use InvalidArgumentException;
use SilverStripe\Framework\Injector\Factory;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\DbDumper;

/**
 * Picks the right DB backend for dumping / importing
 */
class DBDumperFactory implements Factory
{
    /**
     * Creates a new service instance.
     *
     * @param string $service The class name of the service.
     * @param array  $params The constructor parameters.
     * @return DbDumper
     */
    public function create($service, array $params = array())
    {
        // Build class from type field

        global $databaseConfig;

        $args = $databaseConfig;
        $backend = $this->getBackend($args['type']);

        // Mandatory arguments
        $backend->setDbName($args['database'] ?: '');
        $backend->setHost($args['server'] ?: '');
        $backend->setUserName($args['username'] ?: '');
        $backend->setPassword($args['password'] ?: '');

        // Optional arguments
        if (isset($args['port'])) {
            $backend->setPort((int)$args['port']);
        }

        return $backend;
    }

    /**
     * @param string $type
     * @return DbDumper|MySql|PostgreSql
     */
    protected function getBackend($type)
    {
        // Get backend to dump
        switch ($type) {
            case 'MySQLPDODatabase':
            case 'MySQLDatabase':
            case 'MyPDODatabase':
                return MySql::create();
            case 'SQLite3PDODatabase':
            case 'SQLite3Database':
            case 'SQLitePDODatabase':
            case 'SQLiteDatabase':
                throw new InvalidArgumentException("{$type} is not supported");
                break;
            case 'PostgrePDODatabase':
            case 'PostgreSQLDatabase':
                return PostgreSql::create();
            default:
                throw new InvalidArgumentException("Invalid DB class {$type}");
        }
    }
}
