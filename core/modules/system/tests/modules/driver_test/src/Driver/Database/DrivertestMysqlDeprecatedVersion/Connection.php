<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysqlDeprecatedVersion;

use Drupal\Core\Database\Driver\mysql\Connection as CoreConnection;

/**
 * MySQL test implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends CoreConnection {

  /**
   * Constructs a Connection object.
   */
  public function __construct(\PDO $connection, array $connection_options = []) {
    // Alias the MySQL classes to avoid having unnecessary copies.
    foreach (['Delete', 'Insert', 'Merge', 'Schema', 'Upsert', 'Select', 'Update'] as $class) {
      class_alias('Drupal\\Core\\Database\\Driver\\mysql\\' . $class, 'Drupal\\driver_test\\Driver\\Database\\DrivertestMysqlDeprecatedVersion\\' . $class);
    }
    parent::__construct($connection, $connection_options);
  }

  /**
   * Hardcoded database server version.
   *
   * Faking that we are on a deprecated database.
   *
   * @var string
   */
  protected $databaseVersion = '5.5.2';

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'DrivertestMysqlDeprecatedVersion';
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    return $this->databaseVersion;
  }

}
