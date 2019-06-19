## Spin DB

Ensures regular DB backup and off-site storage via AWS S3. Useful for when you have a client you want to keep.

A cron task will regularly create DB backups of your website, archive the files, and upload them to S3. The module
will also rotate your backups to ensure that it retains a larger number of recently created backups,
and fewer of older backups, to ensure that space is not unnecessarily consumed.

The default schedule is:

 - Backup every night at 2am
 - Keeps daily backups for 7 days
 - Keeps monthly backups for 4 months
 - Keeps yearly backups each year
 
This default schedule can be tweaked via config

## Use this module if:

 - You are running SilverStripe 4.0 and above
 - You have a small to medium website
 - Self-hosting on a non-PaaS provider
 - Have access to an AWS bucket
 - Don't want to baby-sit backups
 
## Don't use this module if:

 - Your web server has limited disk space 
 - Your website has a huge database
 - You need professional archival / SLA storage and backup requirements
 - Late-night performance degradation could cause significant issues
 
## Installation

Install your module into your silverstripe project

```bash
composer require littlegiant/silverstripe-spindb
```

## AWS Configuration

Configure access to your AWS bucket. The below environment variables should be configured either directly
as env on your server, or in `.env` in your project root.

```php
define('SPINDB_AWS_S3_BUCKET', '<thebucketname>');
define('SPINDB_AWS_REGION', 'ap-southeast-2'); # Or your aws region
define('SPINDB_AWS_ACCESS_KEY_ID', '<my-access-key>');
define('SPINDB_AWS_SECRET_ACCESS_KEY', '<my-secret>');
```

If you want to authenticate via your local AWS credentials (Stored in `~/.aws/credentials`) then
you can provide a profile name instead.

```php
define('SPINDB_AWS_S3_BUCKET', '<thebucketname>');
define('SPINDB_AWS_REGION', 'ap-southeast-2'); # Or your aws region
define('SPINDB_AWS_PROFILE', 'profilename'); # The profile name containing your authentication credentials. Can be `default`
```

If you are running this site on AWS you can provide access via IAM instead, and you only need to
specify the following. This is the bare minimum configuration necessary for the module to run.

```php
define('SPINDB_AWS_S3_BUCKET', '<thebucketname>');
define('SPINDB_AWS_REGION', 'ap-southeast-2'); # Or your aws region
```

By default DB backups are written to the `{baseurl}/db_{date}{ext}` path within the bucket, but this can be configured.

```php
define('SPINDB_PATH', '{baseurl}/db_{date}{ext}"
```

Supported vars:
 - `{baseurl}` Value of `BASE_URL` var
 - `{date}` Date the archive was created (ISO_8601)
 - `{time}` Time the archive was created (ISO_8601)
 - `{ext}` File extension created, e.g. `.sql`

## Schedule / rotation configuration

You can configure the time of day that the task occurs, or even how frequently it runs.

```php
define('SPINDB_SCHEDULE', '0 2 * * *'); # Every night at 2am
```

If you want to backup less frequently you can adjust the day

```php
define('SPINDB_SCHEDULE', '0 2 */2 * *'); # Every second night at 2am
```

You can configure the number of daily, weekly, monthly, and yearly backups

For each of the below, 0 means keep no backups, -1 means keep unlimited backups (use with care)

```php
define('SPINDB_KEEP_DAILY', '7'); # Default to 1 week of backups
define('SPINDB_KEEP_WEEKLY', '0'); # Default to no weekly backups
define('SPINDB_KEEP_WEEKLY_DAY', '0'); # If keeping weekly backups set the day of the week to keep (0/7 = sunday, 1 = monday, etc).
define('SPINDB_KEEP_MONTHLY', '4'); # Default to 4 months of monthly backups
define('SPINDB_KEEP_MONTHLY_DAY', '1'); # Day of the month to keep. Archaic 1-based index sorry.
define('SPINDB_KEEP_YEARLY', '-1'); # Default to keep unlimited yearly backups.
define('SPINDB_KEEP_YEARLY_DAY', '0'); # Day of the year to keep. 0-365. (0 is Jan 1)
```

You can also configure an alert email to notify when a backup is created.

```php
define('SPINDB_ALERT_EMAIL', 'webmaster@littlegiant.co.nz');
```
