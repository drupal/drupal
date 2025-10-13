<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel\Migrate\d6;

use Drupal\filter\Plugin\migrate\process\d6\FilterFormatPermission;
use Drupal\migrate\Plugin\Migration;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests conversion of format serial to string id in permission name.
 */
#[CoversClass(FilterFormatPermission::class)]
#[Group('filter')]
#[RunTestsInSeparateProcesses]
class FilterFormatPermissionTest extends MigrateDrupalTestBase {

  /**
   * Tests configurability of filter_format migration name.
   *
   * @legacy-covers ::__construct
   */
  public function testConfigurableFilterFormat(): void {
    $migration = Migration::create($this->container, [], 'custom_migration', []);
    $filterFormatPermissionMigration = FilterFormatPermission::create($this->container, ['migration' => 'custom_filter_format'], 'custom_filter_format', [], $migration);
    $reflected_config = new \ReflectionProperty($filterFormatPermissionMigration, 'configuration');
    $config = $reflected_config->getValue($filterFormatPermissionMigration);
    $this->assertEquals('custom_filter_format', $config['migration']);
  }

}
