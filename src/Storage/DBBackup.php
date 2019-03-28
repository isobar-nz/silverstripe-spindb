<?php

namespace LittleGiant\SpinDB\Storage;

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
    public function getKey()
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
    public function matches($date)
    {
        return strtotime($this->date) === strtotime($date);
    }
}
