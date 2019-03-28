<?php

namespace LittleGiant\SpinDB\Database;

/**
 * Abstract interface for dumping the current database to the given file path
 */
interface Dumper
{
    /**
     * Dump the database
     *
     * @param string $file Full path to save file to
     * @return $this
     */
    public function dumpToFile($file);
}
