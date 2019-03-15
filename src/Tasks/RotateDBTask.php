<?php

namespace LittleGiant\SpinDB\Tasks;

use Exception;
use LittleGiant\SpinDB\Configuration\RotateConfig;
use LittleGiant\SpinDB\Storage\DBBackup;
use LittleGiant\SpinDB\Storage\RotateStorage;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;

class RotateDBTask extends BuildTask implements CronTask
{
    private static $segment = 'RotateDBTask';

    protected $title = 'Backup DB to AWS';

    protected $description = 'Backs up DB to AWS, rotating backups over a period of time';

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
        // Start by enumerating all files on the server
        $task = RotateStorage::singleton();
        $files = $task->getFiles();


        // Check if we have a file matching today's date
        $now = DBDatetime::now()->Format(DBDate::ISO_DATE);
        if (!$this->findFile($files, $now)) {
            // @todo - create and upload backup for today
        }
    }

    /**
     * @param DBBackup[] $files
     * @param string     $date Date to find
     * @return DBBackup|null
     */
    protected function findFile($files, $date)
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
     * @param HTTPRequest $request
     * @throws Exception
     */
    public function run($request)
    {
        $this->process();
    }
}
