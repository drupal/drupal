<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests source_module selection.
 */
#[Group('migrate_drupal_ui')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class SourceModuleTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migration_source_module_test'];

  /**
   * Tests the source_module is set when not in source plugin annotation.
   */
  public function testSourceModuleInMigration(): void {
    $this->expectDeprecation('Class "Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase" as extended by "Drupal\migration_source_module_test\Plugin\migrate\source\NoSourceModule" is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533564');
    $migration = \Drupal::service('plugin.manager.migration')->createInstance("migrate_source_module_test");
    $this->assertSame('foo', $migration->getSourcePlugin()->getSourceModule());
  }

}
