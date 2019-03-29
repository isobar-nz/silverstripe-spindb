<?php

namespace LittleGiant\SpinDB\Configuration;

use Exception;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;

class RotateConfig
{
    const METHOD_GZIP = 'gzip';

    const METHOD_NONE = 'none';

    /**
     * Args that can be evaluated from the current path.
     * These should be consistent for all backups in this application.
     *
     * @return array
     */
    protected static function getFixedArgs(): array
    {
        return [
            'basepath' => Convert::raw2htmlid(BASE_PATH),
            'baseurl'  => Convert::raw2htmlid(parse_url(Director::absoluteBaseURL(), PHP_URL_HOST)),
            'ext'      => '.' . self::extension(),
        ];
    }

    /**
     * Get "now" args which represent a new backup.
     *
     * @return array
     */
    public static function getCurrentArgs(): array
    {
        return [
            'date' => DBDatetime::now()->Format(DBDate::ISO_DATE),
            'time' => DBDatetime::now()->Format('HH.mm.ss'),
        ];
    }

    /**
     * Get all variable arguments and their respective patterns
     *
     * @return array
     */
    protected static function getVariableArgPatterns(): array
    {
        return [
            'date' => '(?<date>\d{4}-\d{2}-\d{2})',
            'time' => '(?<time>\d{2}[.]\d{2}[.]\d{2})',
        ];
    }

    /**
     * Get schedule
     *
     * @return string Cron pattern
     */
    public static function schedule(): string
    {
        return Environment::getEnv('SPINDB_SCHEDULE') ?: '0 2 * * *';
    }

    /**
     * Get destination path
     *
     * @param array $arguments Optional arguments to substitute.
     * @return string
     * @throws Exception
     */
    public static function path($arguments = []): string
    {
        $arguments = array_merge(self::getFixedArgs(), $arguments);
        $pattern = Environment::getEnv('SPINDB_PATH') ?: '{baseurl}/db_{date}{ext}';
        if (!strstr($pattern, '{date}')) {
            throw new Exception('SPINDB_PATH variable must contain {date}');
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
     * Parse s3 key into date / time parts.
     *
     * @param string $path
     * @return array|null
     * @throws Exception
     */
    public static function parse($path): ?array
    {
        // Get path as an expression, adding in variables as matching groups
        $basePattern = self::path();
        $patterns = self::getVariableArgPatterns();
        $pattern = '#' . preg_quote($basePattern, '#') . '#';
        foreach ($patterns as $name => $value) {
            $pattern = str_replace(preg_quote("{{$name}}", '#'), $value, $pattern);
        }

        // Match path against the pattern we've just built
        if (!preg_match($pattern, $path, $matches)) {
            return null;
        }

        // Return array of matched parts
        $result = [];
        foreach ($patterns as $name => $value) {
            if (isset($matches[$name])) {
                $result[$name] = $matches[$name];
            }
        }
        return $result;
    }

    /**
     * Get bucket name to back up to.
     * Note: If not configured SPINDB will be disabled
     *
     * @return string|null
     */
    public static function bucket(): ?string
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
    public static function region(): ?string
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
    public static function accesKeyID(): ?string
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
    public static function secretAccessKey(): ?string
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
    public static function profile(): ?string
    {
        return Environment::getEnv('SPINDB_AWS_PROFILE')
            ?: Environment::getEnv('AWS_PROFILE')
                ?: null;
    }

    /**
     * Number of daily backups to keep
     *
     * @return int
     */
    public static function keepDaily(): int
    {
        return self::getNumeric('SPINDB_KEEP_DAILY', 7);
    }

    /**
     * Number of weekly backups to keep
     *
     * @return int
     */
    public static function keepWeekly(): int
    {
        return self::getNumeric('SPINDB_KEEP_WEEKLY', 0);
    }

    /**
     * Day of week to keep. 0 = Sunday, 1 = Monday, etc
     *
     * @return int
     */
    public static function keepWeeklyDay(): int
    {
        return self::getNumeric('SPINDB_KEEP_WEEKLY_DAY', 0);
    }

    /**
     * Number of monthly backups to keep
     *
     * @return int
     */
    public static function keepMonthly(): int
    {
        return self::getNumeric('SPINDB_KEEP_MONTHLY', 4);
    }

    /**
     * Day of month to keep. Starts at 1 obviously.
     *
     * @return int
     */
    public static function keepMonthlyDay(): int
    {
        return self::getNumeric('SPINDB_KEEP_MONTHLY_DAY', 1);
    }

    /**
     * Number of yearly backups to keep
     *
     * @return int
     */
    public static function keepYearly(): int
    {
        return self::getNumeric('SPINDB_KEEP_YEARLY', -1);
    }

    /**
     * Day of year to backup. Starts at 1.
     *
     * @return int
     */
    public static function keepYearlyDay(): int
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
    protected static function getNumeric(string $var, int $default): int
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
     * @return string|null
     */
    public static function archiveMethod(): ?string
    {
        $method = Environment::getEnv('SPINDB_ARCHIVE');
        switch ($method) {
            case self::METHOD_NONE:
                return null;
            case self::METHOD_GZIP:
            default:
                return self::METHOD_GZIP;
        }
    }

    /**
     * Get file extension to use (not including .)
     *
     * @return string
     */
    public static function extension(): string
    {
        $method = static::archiveMethod();
        return $method
            ? "sql.{$method}"
            : 'sql';
    }
}
