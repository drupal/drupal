<?php

namespace Drupal\database_statement_monitoring_test\sqlite;

use Drupal\Core\Database\Driver\sqlite\Connection as BaseConnection;
use Drupal\database_statement_monitoring_test\LoggedStatementsTrait;

/**
 * SQlite Connection class that can log executed queries.
 */
class Connection extends BaseConnection {
  use LoggedStatementsTrait;

}
