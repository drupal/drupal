<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

use Drupal\Core\Database\Driver\pgsql\NativeUpsert as CoreNativeUpsert;

/**
 * PostgreSQL implementation of native \Drupal\Core\Database\Query\Upsert.
 */
class NativeUpsert extends CoreNativeUpsert {}
