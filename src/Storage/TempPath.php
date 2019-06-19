<?php

namespace LittleGiant\SpinDB\Storage;

class TempPath
{
    /**
     * Make a temp dir
     *
     * @param string $prefix
     * @param int    $mode
     * @param int    $maxAttempts
     * @return bool|string
     */
    public static function tempdir($prefix = 'tmp_', $mode = 0700, $maxAttempts = 1000)
    {
        /* Trim trailing slashes from $dir. */
        $dir = rtrim(TEMP_FOLDER, DIRECTORY_SEPARATOR);

        /* If we don't have permission to create a directory, fail, otherwise we will
         * be stuck in an endless loop.
         */
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        /* Make sure characters in prefix are safe. */
        if (strpbrk($prefix, '\\/:*?"<>|') !== false) {
            return false;
        }

        /* Attempt to create a random directory until it works. Abort if we reach
         * $maxAttempts. Something screwy could be happening with the filesystem
         * and our loop could otherwise become endless.
         */
        $attempts = 0;
        do {
            $path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
        } while (!mkdir($path, $mode) &&
            $attempts++ < $maxAttempts
        );

        return $path;
    }
}
