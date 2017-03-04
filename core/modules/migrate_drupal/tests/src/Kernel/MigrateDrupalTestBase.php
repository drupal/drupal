<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Base class for Drupal migration tests.
 */
abstract class MigrateDrupalTestBase extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'field', 'migrate_drupal', 'options', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['migrate_drupal', 'system']);
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * @param string $path
   *   Path to the dump file.
   */
  protected function loadFixture($path) {
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());

    if (substr($path, -3) == '.gz') {
      $path = 'compress.zlib://' . $path;
    }
    require $path;

    Database::setActiveConnection($default_db);
  }

}
