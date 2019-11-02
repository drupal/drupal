<?php

namespace Drupal\Tests\filter\Kernel\Migrate\d6;

use Drupal\filter\Plugin\migrate\process\d6\FilterFormatPermission;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests conversion of format serial to string id in permission name.
 *
 * @coversDefaultClass \Drupal\filter\Plugin\migrate\process\d6\FilterFormatPermission
 *
 * @group filter
 */
class FilterFormatPermissionTest extends MigrateDrupalTestBase {

  /**
   * Tests configurability of filter_format migration name.
   *
   * @covers ::__construct
   */
  public function testConfigurableFilterFormat() {
    $migration = Migration::create($this->container, [], 'custom_migration', []);
    $filterFormatPermissionMigration = FilterFormatPermission::create($this->container, ['migration' => 'custom_filter_format'], 'custom_filter_format', [], $migration);
    $config = $this->readAttribute($filterFormatPermissionMigration, 'configuration');
    $this->assertEquals($config['migration'], 'custom_filter_format');
  }

  /**
   * Tests legacy plugin usage.
   *
   * @group legacy
   *
   * @expectedDeprecation Passing a migration process plugin as the fourth argument to Drupal\filter\Plugin\migrate\process\d6\FilterFormatPermission::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testLegacyConstruct() {
    $process_plugin = $this->prophesize(MigrateProcessInterface::class)->reveal();
    $plugin = new FilterFormatPermission([], '', [], $this->prophesize(MigrationInterface::class)->reveal(), $process_plugin);
    $this->assertSame($process_plugin, $this->readAttribute($plugin, 'migrationPlugin'));
  }

}
