<?php

namespace Drupal\database_statement_monitoring_test\pgsql;

use Drupal\pgsql\Driver\Database\pgsql\Connection as BaseConnection;
use Drupal\database_statement_monitoring_test\LoggedStatementsTrait;

/**
 * PostgreSQL Connection class that can log executed queries.
 */
class Connection extends BaseConnection {
  use LoggedStatementsTrait;

}
