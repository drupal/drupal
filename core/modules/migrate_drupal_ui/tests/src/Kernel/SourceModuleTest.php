<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests source_module selection.
 *
 * @group migrate_drupal_ui
 */
class SourceModuleTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migration_source_module_test'];

  /**
   * Tests the source_module is set when not in source plugin annotation.
   */
  public function testSourceModuleInMigration(): void {
    $migration = \Drupal::service('plugin.manager.migration')->createInstance("migrate_source_module_test");
    $this->assertSame('foo', $migration->getSourcePlugin()->getSourceModule());
  }

}
