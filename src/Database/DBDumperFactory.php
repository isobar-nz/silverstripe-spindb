<?php

namespace LittleGiant\SpinDB\Database;

use InvalidArgumentException;
use LittleGiant\SpinDB\Configuration\RotateConfig;
use SilverStripe\Core\Injector\Factory;
use SilverStripe\ORM\DB;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Databases\Sqlite;
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
        $args = DB::getConfig();
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

        // Set compression (note: Only GZIP supported at the moment)
        if (RotateConfig::archiveMethod() === RotateConfig::METHOD_GZIP) {
            $backend->useCompressor(new GzipCompressor());
        }

        return $backend;
    }

    /**
     * @param string $type
     * @return DbDumper
     */
    protected function getBackend($type)
    {
        // Get backend to dump
        switch ($type) {
            case 'MySQLPDODatabase':
            case 'MyPDODatabase':
                return MySql::create();
            case 'SQLite3PDODatabase':
            case 'SQLite3Database':
            case 'SQLitePDODatabase':
            case 'SQLiteDatabase':
                return Sqlite::create();
            case 'PostgrePDODatabase':
            case 'PostgreSQLDatabase':
                return PostgreSql::create();
            default:
                throw new InvalidArgumentException("Invalid DB class {$type}");
        }
    }
}
