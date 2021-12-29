<?php

namespace Drupal\database_statement_monitoring_test\mysql;

use Drupal\mysql\Driver\Database\mysql\Connection as BaseConnection;
use Drupal\database_statement_monitoring_test\LoggedStatementsTrait;

/**
 * MySQL Connection class that can log executed queries.
 */
class Connection extends BaseConnection {
  use LoggedStatementsTrait;

}
