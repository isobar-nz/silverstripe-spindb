<?php

use LittleGiant\SpinDB\Health\BackupCheck;

EnvironmentCheckSuite::register('check', BackupCheck::class, "Is the database backup saving to S3 daily?");
