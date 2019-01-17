<?php

namespace LittleGiant\SpinDB\Tasks;

use SilverStripe\CronTask\Interfaces\CronTask;

class RotateDBTask implements CronTask
{
    /**
     * Return a string for a CRON expression
     *
     * @return string
     */
    public function getSchedule()
    {
        // TODO: Implement getSchedule() method.
    }

    /**
     * When this script is supposed to run the CronTaskController will execute
     * process().
     *
     * @return void
     */
    public function process()
    {
        // TODO: Implement process() method.
    }
}
