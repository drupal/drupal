<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateViewModesTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests Drupal 6 view modes to Drupal 8 migration.
 */
class MigrateViewModesTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate view modes to entity.view_mode.*.*.yml',
      'description'  => 'Migrate view modes',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_view_modes');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6FieldInstance.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests Drupal 6 view modes to Drupal 8 migration.
   */
  public function testViewModes() {
    // Test a new view mode.
    $view_mode = entity_load('view_mode', 'node.preview');
    $this->assertEqual(is_null($view_mode), FALSE, 'Preview view mode loaded.');
    $this->assertEqual($view_mode->label(), 'Preview', 'View mode has correct label.');
    // Test the Id Map.
    $this->assertEqual(array('node', 'preview'), entity_load('migration', 'd6_view_modes')->getIdMap()->lookupDestinationID(array(1)));
  }

}
