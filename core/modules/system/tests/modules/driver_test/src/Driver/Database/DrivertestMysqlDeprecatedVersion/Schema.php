<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysqlDeprecatedVersion;

include_once dirname(__DIR__, 8) . '/mysql/src/Driver/Database/mysql/Schema.php';

use Drupal\mysql\Driver\Database\mysql\Schema as CoreSchema;

/**
 * MySQL test implementation of \Drupal\Core\Database\Schema.
 */
class Schema extends CoreSchema {}
