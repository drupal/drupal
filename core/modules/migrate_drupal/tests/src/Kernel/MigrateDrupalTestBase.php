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
  protected static $modules = [
    'system',
    'user',
    'field',
    'migrate_drupal',
    'options',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $module_handler = \Drupal::moduleHandler();
    if ($module_handler->moduleExists('node')) {
      $this->installEntitySchema('node');
    }
    if ($module_handler->moduleExists('comment')) {
      $this->installEntitySchema('comment');
    }
    if ($module_handler->moduleExists('taxonomy')) {
      $this->installEntitySchema('taxonomy_term');
    }
    if ($module_handler->moduleExists('user')) {
      $this->installEntitySchema('user');
    }

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
