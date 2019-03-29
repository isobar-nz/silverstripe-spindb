<?php

namespace LittleGiant\SpinDB\Storage;

use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use LittleGiant\SpinDB\Configuration\RotateConfig;

/**
 * Matches a DB backup
 */
class DBBackup
{
    /**
     * Date this backup was created (ISO_8601)
     *
     * @var string
     */
    protected $date;

    /**
     * Time this backup was created (optional)
     *
     * @var string|null
     */
    protected $time;

    public function __construct($parts = [])
    {
        if (empty($parts['date'])) {
            throw new InvalidArgumentException("Missing date");
        }

        $this->date = $parts['date'];

        // Save optional time
        if (isset($parts['time'])) {
            $this->time = $parts['time'];
        }
    }

    /**
     * @return string|null
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get combined date / time
     *
     * @return string
     */
    public function getDateTime()
    {
        $date = $this->getDate();
        $time = $this->getTime();
        if ($time) {
            return "{$date} {$time}";
        }
        return $date;
    }

    /**
     * Build AWS key this backup corresponds to
     *
     * @return string
     * @throws Exception
     */
    public function getKey(): string
    {
        $parts = [
            'date' => $this->getDate(),
            'time' => $this->getTime(),
        ];
        return RotateConfig::path($parts);
    }

    /**
     * Determine if this backup matches the given date
     *
     * @param string $date
     * @return bool
     */
    public function matches($date): bool
    {
        return strtotime($this->date) === strtotime($date);
    }

    /**
     * Check if this file should be purged
     *
     * @param string $today Date to use for today
     * @return bool
     * @throws Exception
     */
    public function requirePurging(string $today): bool
    {
        // Always keep current date
        if ($this->matches($today)) {
            return false;
        }
        if ($this->isDaily($today)) {
            return false;
        }
        if ($this->isWeekly($today)) {
            return false;
        }
        if ($this->isMonthly($today)) {
            return false;
        }
        if ($this->isYearly($today)) {
            return false;
        }
        return true;
    }

    /**
     * Check if this item matches one of the daily rules
     *
     * @param string $today
     * @return bool
     * @throws Exception
     */
    protected function isDaily(string $today): bool
    {
        // Check if we keep any daily records
        $keepDaily = RotateConfig::keepDaily();
        if (!$keepDaily) {
            return false;
        }

        return $this->isNewerThan($today, $keepDaily, 'D');
    }

    /**
     * Check if this item matches one of the weekly rules
     *
     * @param string $today
     * @return bool
     * @throws Exception
     */
    protected function isWeekly(string $today): bool
    {
        // Check if we keep any weekly records
        $keepWeekly = RotateConfig::keepWeekly();
        if (!$keepWeekly) {
            return false;
        }

        // Check if we are past the weekly limit
        if (!$this->isNewerThan($today, $keepWeekly, 'W')) {
            return false;
        }

        // Check if day of week matches (%7 for safety)
        return (int)date('w', strtotime($this->getDate())) === (RotateConfig::keepWeeklyDay() % 7);
    }

    /**
     * Check if this item matches one of the monthly rules
     *
     * @param string $today
     * @return bool
     * @throws Exception
     */
    protected function isMonthly(string $today): bool
    {
        // Check if we keep any weekly records
        $keepMonthly = RotateConfig::keepMonthly();
        if (!$keepMonthly) {
            return false;
        }

        // Check if we are past the monthly limit
        if (!$this->isNewerThan($today, $keepMonthly, 'M')) {
            return false;
        }

        // Check if day of month matches
        return (int)date('j', strtotime($this->getDate())) === (int)RotateConfig::keepMonthlyDay();
    }

    /**
     * Check if this item matches one of the yearly rules
     *
     * @param string $today
     * @return bool
     * @throws Exception
     */
    protected function isYearly(string $today): bool
    {
        // Check if we keep any weekly records
        $keepYearly = RotateConfig::keepYearly();
        if (!$keepYearly) {
            return false;
        }

        // Check if we are past the monthly limit
        if (!$this->isNewerThan($today, $keepYearly, 'Y')) {
            return false;
        }

        // Check if day of year matches (note: z is 0 based, day of year is 1 based)
        return (int)date('z', strtotime($this->getDate())) === (int)RotateConfig::keepYearlyDay();
    }

    /**
     * @param string $date Date to use for today
     * @param string $age Number of units (e.g. days)
     * @param string $units Units to check. E.g. 'D' for days, 'W' for weeks
     * @return bool True if the date is newer than (but not equal to) than the given age
     * @throws Exception
     */
    protected function isNewerThan($date, $age, $units): bool
    {
        // Negative ranges (e.g. -1 weekly backups) means there is no max age
        if ($age < 0) {
            return true;
        }

        // Check if we are within the daily backup limit
        $minDate = new DateTime($date);
        $minDate->sub(new DateInterval("P{$age}{$units}"));
        $self = new DateTime($this->getDate());
        return $self > $minDate;
    }
}
