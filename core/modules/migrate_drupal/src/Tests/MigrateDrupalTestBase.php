<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\MigrateDrupalTestBase.
 */

namespace Drupal\migrate_drupal\Tests;

use Drupal\migrate\Tests\MigrateTestBase;
use Drupal\migrate\Entity\Migration;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Base class for Drupal migration tests.
 */
abstract class MigrateDrupalTestBase extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field', 'migrate_drupal', 'options', 'file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $tables = file_scan_directory($this->getDumpDirectory(), '/.php$/', array('recurse' => FALSE));
    $this->loadDumps(array_keys($tables));

    $this->installEntitySchema('user');
    $this->installConfig(['migrate_drupal', 'system']);
  }

  /**
   * Returns the path to the dump directory.
   *
   * @return string
   *   A string that represents the dump directory path.
   */
  protected function getDumpDirectory() {
    return __DIR__ . '/Table';
  }

  /**
   * Turn all the migration templates for the specified drupal version into
   * real migration entities so we can test them.
   *
   * @param string $version
   *  Drupal version as provided in migration_tags - e.g., 'Drupal 6'.
   */
  protected function installMigrations($version) {
    $migration_templates = \Drupal::service('migrate.template_storage')->findTemplatesByTag($version);
    $migrations = \Drupal::service('migrate.migration_builder')->createMigrations($migration_templates);
    foreach ($migrations as $migration) {
      try {
        $migration->save();
      }
      catch (PluginNotFoundException $e) {
        // Migrations requiring modules not enabled will throw an exception.
        // Ignoring this exception is equivalent to placing config in the
        // optional subdirectory - the migrations we require for the test will
        // be successfully saved.
      }
    }
  }

  /**
   * Test that the source plugin provides a valid query and a valid count.
   */
  public function testSourcePlugin() {
    if (isset($this->migration)) {
      $source = $this->migration->getSourcePlugin();
      // Make sure a SqlBase source has a valid query.
      if ($source instanceof SqlBase) {
        /** @var SqlBase $source */
        $this->assertTrue($source->query() instanceof SelectInterface, 'SQL source plugin has valid query');
      }
      // Validate that any source returns a valid count.
      $this->assertTrue(is_numeric($source->count()), 'Source plugin returns valid count');
    }
  }

}
