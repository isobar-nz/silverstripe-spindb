<?php

namespace LittleGiant\SpinDB\Health;

use LittleGiant\SpinDB\Storage\RotateStorage;
use SilverStripe\EnvironmentCheck\EnvironmentCheck;
use SilverStripe\ORM\FieldType\DBDatetime;

class BackupCheck implements EnvironmentCheck
{
    public function check()
    {
        $bestResult = EnvironmentCheck::ERROR;
        foreach (RotateStorage::singleton()->getFiles() as $file) {
            // Check if we can parse the created date
            $createdTimestamp = strtotime($file->getDateTime());
            if (!$createdTimestamp) {
                continue;
            }

            // If best result is < 1 day ago, success
            $oneDayAgo = strtotime("-1 day", DBDatetime::now()->getTimestamp());
            if ($oneDayAgo < $createdTimestamp) {
                return EnvironmentCheck::OK;
            }

            // If best result is < 2 days ago, warn (unless we later find a more recent item)
            $twoDaysAgo = strtotime("-2 days", DBDatetime::now()->getTimestamp());
            if ($createdTimestamp > $twoDaysAgo) {
                $bestResult = EnvironmentCheck::WARNING;
            }
        }

        return $bestResult;
    }
}
