<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateViewModesTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Entity\Entity\EntityViewMode;

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
    $this->loadDumps([
      'ContentNodeFieldInstance.php',
      'ContentNodeField.php',
      'ContentFieldTest.php',
      'ContentFieldTestTwo.php',
      'ContentFieldMultivalue.php',
    ]);
    $this->executeMigration('d6_view_modes');
  }

  /**
   * Tests Drupal 6 view modes to Drupal 8 migration.
   */
  public function testViewModes() {
    // Test a new view mode.
    $view_mode = EntityViewMode::load('node.preview');
    $this->assertIdentical(FALSE, is_null($view_mode), 'Preview view mode loaded.');
    $this->assertIdentical('Preview', $view_mode->label(), 'View mode has correct label.');
    // Test the Id Map.
    $this->assertIdentical(array('node', 'preview'), entity_load('migration', 'd6_view_modes')->getIdMap()->lookupDestinationID(array(1)));
  }

}
