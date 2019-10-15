<?php

namespace Drupal\database_statement_monitoring_test\pgsql;

use Drupal\Core\Database\Driver\pgsql\Connection as BaseConnection;
use Drupal\database_statement_monitoring_test\LoggedStatementsTrait;

/**
 * PostgreSQL Connection class that can log executed queries.
 */
class Connection extends BaseConnection {
  use LoggedStatementsTrait;

}
