<?php

namespace LittleGiant\SpinDB\Database;

use Spatie\DbDumper\DbDumper;

/**
 * Dumps DB using DBDumber library
 */
class DBDumperDumper implements Dumper
{
    /**
     * @var DbDumper
     */
    protected $backend = null;

    public function setBackend(DbDumper $backend)
    {
        $this->backend = $backend;
        return $this;
    }

    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Dump the database
     *
     * @param string $file Full path to save file to
     * @return $this
     */
    public function dumpToFile($file)
    {
        // TODO: Implement dumpToFile() method.
    }
}
