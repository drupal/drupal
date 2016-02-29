<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateViewModesTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Migrate view modes.
 *
 * @group migrate_drupal_6
 */
class MigrateViewModesTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
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
    // Test the ID map.
    $this->assertIdentical(array('node', 'preview'), Migration::load('d6_view_modes')->getIdMap()->lookupDestinationID(array(1)));
  }

}
