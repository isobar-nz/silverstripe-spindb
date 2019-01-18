<?php

namespace Configuration;

use InvalidArgumentException;
use SilverStripe\Core\Environment;

class RotateConfig
{
    const METHOD_ZIP = 'zip';

    const METHOD_NONE = 'none';

    /**
     * Get schedule
     *
     * @return string Cron pattern
     */
    public static function schedule()
    {
        return Environment::getEnv('SPINDB_SCHEDULE') ?: '0 2 * * *';
    }

    /**
     * Get destination path
     *
     * @param array $arguments Optional arguments to substitute.
     * @return string
     */
    public static function path($arguments = [])
    {
        $pattern = Environment::getEnv('SPINDB_PATH') ?: '{baseurl}/db_{date}{ext}';
        if (empty($arguments)) {
            return $pattern;
        }

        // Substitutions
        $replace = [];
        $with = [];
        foreach ($arguments as $key => $value) {
            $replace[] = "{{$key}}";
            $with[] = $value;
        }

        return str_replace($replace, $with, $pattern);
    }

    /**
     * Get bucket name to back up to.
     * Note: If not configured SPINDB will be disabled
     *
     * @return string|null
     */
    public static function bucket()
    {
        return Environment::getEnv('SPINDB_AWS_S3_BUCKET')
            ?: Environment::getEnv('AWS_S3_BUCKET')
                ?: null;
    }

    /**
     * AWS Region code
     *
     * @return string
     */
    public static function region()
    {
        return Environment::getEnv('SPINDB_AWS_REGION')
            ?: Environment::getEnv('AWS_REGION')
                ?: null;
    }

    /**
     * AWS API access key to use. Optional if using IAM
     *
     * @return string
     */
    public static function accesKeyID()
    {
        return Environment::getEnv('SPINDB_AWS_ACCESS_KEY_ID')
            ?: Environment::getEnv('AWS_ACCESS_KEY_ID')
                ?: null;
    }

    /**
     * AWS API secret to use. Optional if using IAM
     *
     * @return string
     */
    public static function secretAccessKey()
    {
        return Environment::getEnv('SPINDB_AWS_SECRET_ACCESS_KEY')
            ?: Environment::getEnv('AWS_SECRET_ACCESS_KEY')
                ?: null;
    }

    /**
     * AWS profile name to use. Defaults to 'default'
     *
     * @return string
     */
    public static function profile()
    {
        return Environment::getEnv('SPINDB_AWS_PROFILE')
            ?: Environment::getEnv('AWS_PROFILE')
                ?: 'default';
    }

    /**
     * Number of daily backups to keep
     *
     * @return int
     */
    public static function keepDaily()
    {
        return self::getNumeric('SPINDB_KEEP_DAILY', 7);
    }

    /**
     * Number of weekly backups to keep
     *
     * @return int
     */
    public static function keepWeekly()
    {
        return self::getNumeric('SPINDB_KEEP_WEEKLY', 0);
    }

    /**
     * Day of week to keep. 0 = Sunday, 1 = Monday, etc
     *
     * @return int
     */
    public static function keepWeeklyDay()
    {
        return self::getNumeric('SPINDB_KEEP_WEEKLY_DAY', 0);
    }

    /**
     * Number of monthly backups to keep
     *
     * @return int
     */
    public static function keepMonthly()
    {
        return self::getNumeric('SPINDB_KEEP_MONTHLY', 4);
    }

    /**
     * Day of month to keep. Starts at 1 obviously.
     *
     * @return int
     */
    public static function keepMonthlyDay()
    {
        return self::getNumeric('SPINDB_KEEP_MONTHLY_DAY', 1);
    }

    /**
     * Number of yearly backups to keep
     *
     * @return int
     */
    public static function keepYearly()
    {
        return self::getNumeric('SPINDB_KEEP_YEARLY', -1);
    }

    /**
     * Day of year to backup. Starts at 1.
     *
     * @return int
     */
    public static function keepYearlyDay()
    {
        return self::getNumeric('SPINDB_KEEP_YEARLY', 1);
    }

    /**
     * Get integer value
     *
     * @param string $var Name of var
     * @param int    $default Defaul value if $var isn't provided, or is non-integer
     * @return int
     */
    protected static function getNumeric($var, $default)
    {
        $daily = Environment::getEnv($var);
        if (is_numeric($daily)) {
            return (int)$daily;
        }
        return $default;
    }

    /**
     * Get archive method to use
     *
     * @return string
     */
    public static function archiveMethod()
    {
        $method = Environment::getEnv('SPINDB_ARCHIVE') ?: 'zip';
        switch ($method) {
            case self::METHOD_NONE:
            case self::METHOD_ZIP:
                return $method;
            default:
                throw new InvalidArgumentException("Invalid archive method {$method}");
        }
    }
}
