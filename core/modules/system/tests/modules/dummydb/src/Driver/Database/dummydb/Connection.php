<?php

declare(strict_types=1);

// cspell:ignore dummydb

namespace Drupal\dummydb\Driver\Database\dummydb;

use Drupal\mysql\Driver\Database\mysql\Connection as CoreConnection;

/**
 * DummyDB test implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends CoreConnection {

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'dummydb';
  }

  /**
   * {@inheritdoc}
   */
  public function databaseType() {
    return 'dummydb';
  }

}
