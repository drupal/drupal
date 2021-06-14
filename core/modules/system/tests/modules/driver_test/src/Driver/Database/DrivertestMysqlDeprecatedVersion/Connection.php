<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysqlDeprecatedVersion;

use Drupal\Core\Database\Driver\mysql\Connection as CoreConnection;

/**
 * MySQL test implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends CoreConnection {

  /**
   * Hardcoded database server version.
   *
   * Faking that we are on a deprecated database.
   *
   * @var string
   */
  protected $databaseVersion = '10.2.31-MariaDB-1:10.2.31+maria~bionic-log';

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'DrivertestMysqlDeprecatedVersion';
  }

  /**
   * {@inheritdoc}
   */
  public function isMariaDb(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    return $this->databaseVersion;
  }

  /**
   * {@inheritdoc}
   */
  protected function getServerVersion(): string {
    return $this->databaseVersion;
  }

}
