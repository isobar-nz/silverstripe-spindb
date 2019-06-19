<?php

namespace LittleGiant\SpinDB\Tasks;

use BuildTask;
use Controller;
use Convert;
use CronTask;
use Director;
use Exception;
use Filesystem;
use Injector;
use LittleGiant\SpinDB\Configuration\RotateConfig;
use LittleGiant\SpinDB\Database\Dumper;
use LittleGiant\SpinDB\Storage\DBBackup;
use LittleGiant\SpinDB\Storage\RotateStorage;
use LittleGiant\SpinDB\Storage\TempPath;
use SS_HTTPRequest;

class RotateDBTask extends BuildTask implements CronTask
{
    private static $segment = 'RotateDBTask';

    protected $title = 'Backup DB to AWS';

    protected $description = 'Backs up DB to AWS, rotating backups over a period of time';

    /**
     * Log a message
     *
     * @param string $message
     */
    protected function message($message)
    {
        if (Director::is_cli()) {
            echo $message . "\n";
        } else {
            echo Convert::raw2sql($message) . "<br />\n";
        }
    }

    /**
     * Return a string for a CRON expression
     *
     * @return string
     */
    public function getSchedule()
    {
        return RotateConfig::schedule();
    }

    /**
     * When this script is supposed to run the CronTaskController will execute
     * process().
     *
     * @throws Exception
     */
    public function process()
    {
        $this->message('');
        $parts = RotateConfig::getCurrentArgs();
        $files = $this->buildServerBackups($parts);
        $this->rotateBackups($files, $parts['date']);
    }

    /**
     * @param DBBackup[] $files
     * @param string     $date Date to find
     * @return DBBackup|null
     */
    protected function findFile(array $files, string $date)
    {
        foreach ($files as $file) {
            if ($file->matches($date)) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     * @throws Exception
     */
    public function run($request)
    {
        $this->process();
    }

    /**
     * Ensure that a file for today's date exists, and get all server backups
     *
     * @param array $parts List of arguments to use for 'date' and 'time'
     * @return DBBackup[]|null All backups
     * @throws Exception
     */
    protected function buildServerBackups(array $parts)
    {
        $this->message("Checking server backups");

        // Start by enumerating all files on the server
        $files = RotateStorage::singleton()->getFiles();

        // Check if we have a file matching today's date
        $today = $this->findFile($files, $parts['date']);
        if (!$today) {
            $files[] = $this->createNewBackup($parts);
        }

        return $files;
    }

    /**
     * Build a new backup with the given arguments
     *
     * @param array $nowParts array with 'date' and 'time' fields to use for this backup
     * @return DBBackup
     * @throws Exception
     */
    protected function createNewBackup(array $nowParts): DBBackup
    {
        $this->message("Creating backup for {$nowParts['date']} at {$nowParts['time']}");

        // Build new backup object
        $backup = new DBBackup($nowParts);
        $uploadPath = $backup->getKey();

        // Build a new temporary folder to backup to
        $tempFolder = TempPath::tempdir();
        $tempPath = Controller::join_links($tempFolder, basename($uploadPath));
        try {
            // Dump the database
            /** @var Dumper $dumper */
            $dumper = Injector::inst()->create(Dumper::class);
            $dumper->dumpToFile($tempPath);

            // Upload file to S3
            $this->message("Uploading to {$uploadPath}");
            RotateStorage::singleton()->saveFile($tempPath, $uploadPath);
        } finally {
            // Remove temporary file / folder
            Filesystem::removeFolder($tempFolder);
        }
        return $backup;
    }

    /**
     * Rotate all backups
     *
     * @param DBBackup[] $files
     * @param string     $date Current date time
     * @throws Exception
     */
    protected function rotateBackups(array $files, string $date)
    {
        $this->message("Rotating logs");

        $purge = [];
        foreach ($files as $file) {
            if ($file->requirePurging($date)) {
                $purge[] = $file;
            }
        }

        // Purge all files together
        if ($purge) {
            $count = count($purge);
            $this->message("Purging {$count} files:");

            foreach ($purge as $item) {
                $this->message(" - Purging {$item->getKey}");
                RotateStorage::singleton()->deleteFile($item->getKey());
            }
        }

        $this->message("Rotating done!");
    }
}
