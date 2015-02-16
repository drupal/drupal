<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateViewModesTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Migrate view modes.
 *
 * @group migrate_drupal
 */
class MigrateViewModesTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_view_modes');
    $dumps = array(
      $this->getDumpDirectory() . '/ContentNodeFieldInstance.php',
      $this->getDumpDirectory() . '/ContentNodeField.php',
      $this->getDumpDirectory() . '/ContentFieldTest.php',
      $this->getDumpDirectory() . '/ContentFieldTestTwo.php',
      $this->getDumpDirectory() . '/ContentFieldMultivalue.php',
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
    $view_mode = EntityViewMode::load('node.preview');
    $this->assertIdentical(is_null($view_mode), FALSE, 'Preview view mode loaded.');
    $this->assertIdentical($view_mode->label(), 'Preview', 'View mode has correct label.');
    // Test the Id Map.
    $this->assertIdentical(array('node', 'preview'), entity_load('migration', 'd6_view_modes')->getIdMap()->lookupDestinationID(array(1)));
  }

}
