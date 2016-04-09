<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

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
    $this->assertIdentical(array('node', 'preview'), $this->getMigration('d6_view_modes')->getIdMap()->lookupDestinationID(array(1)));
  }

}
