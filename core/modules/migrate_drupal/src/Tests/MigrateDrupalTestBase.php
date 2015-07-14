<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\MigrateDrupalTestBase.
 */

namespace Drupal\migrate_drupal\Tests;

use Drupal\migrate\Tests\MigrateTestBase;
use Drupal\migrate\Entity\Migration;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Base class for Drupal migration tests.
 */
abstract class MigrateDrupalTestBase extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field', 'migrate_drupal', 'options');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['System.php']);

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
   * {@inheritdoc}
   */
  protected function loadDumps(array $files, $method = 'load') {
    $files = array_map(function($file) { return $this->getDumpDirectory() . '/' . $file; }, $files);
    parent::loadDumps($files, $method);
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
    foreach ($migration_templates as $template) {
      try {
        $migration = Migration::create($template);
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
}
