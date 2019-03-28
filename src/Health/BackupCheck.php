<?php

namespace LittleGiant\SpinDB\Health;

use Exception;
use LittleGiant\SpinDB\Storage\RotateStorage;
use SilverStripe\EnvironmentCheck\EnvironmentCheck;
use SilverStripe\ORM\FieldType\DBDatetime;

class BackupCheck implements EnvironmentCheck
{
    /**
     * @return array
     * @throws Exception
     */
    public function check()
    {
        // Find latest backup
        $lastBackup = 0;
        $lastBackupText = null;
        foreach (RotateStorage::singleton()->getFiles() as $file) {
            // Check if we can parse the created date
            $createdTimestamp = strtotime($file->getDateTime());
            if ($createdTimestamp && $createdTimestamp > $lastBackup) {
                $lastBackup = $createdTimestamp;
                $lastBackupText = $file->getDateTime();
            }
        }

        if (empty($lastBackup)) {
            return [
                EnvironmentCheck::ERROR,
                'No backups found',
            ];
        }

        // If best result is < 1 day ago, success
        $message = "Last backup {$lastBackupText}";
        switch (true) {
            case $lastBackup > strtotime("-1 day", DBDatetime::now()->getTimestamp()):
                $status = EnvironmentCheck::OK;
                break;
            case $lastBackup > strtotime("-2 day", DBDatetime::now()->getTimestamp()):
                $status = EnvironmentCheck::WARNING;
                break;
            default:
                $status = EnvironmentCheck::ERROR;
                break;
        }

        return [
            $status,
            $message,
        ];
    }
}
