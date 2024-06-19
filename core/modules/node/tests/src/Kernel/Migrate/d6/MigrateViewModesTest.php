<?php

declare(strict_types=1);

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
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d6_view_modes');
  }

  /**
   * Tests Drupal 6 view modes to Drupal 8 migration.
   */
  public function testViewModes(): void {
    // Test a new view mode.
    $view_mode = EntityViewMode::load('node.preview');
    $this->assertNotNull($view_mode);
    $this->assertSame('Preview', $view_mode->label(), 'View mode has correct label.');
    // Test the ID map.
    $this->assertSame([['node', 'preview']], $this->getMigration('d6_view_modes')->getIdMap()->lookupDestinationIds([1]));
  }

}
