<?php

declare(strict_types=1);

namespace Drupal\driver_test\Driver\Database\DriverTestMysqlDeprecatedVersion;

use Drupal\mysql\Driver\Database\mysql\Connection as CoreConnection;

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
    return 'DriverTestMysqlDeprecatedVersion';
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
