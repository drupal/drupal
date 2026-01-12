<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\Migration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the migration plugin.
 */
#[CoversClass(Migration::class)]
#[Group('migrate')]
#[RunTestsInSeparateProcesses]
class MigrationTest extends KernelTestBase {

  /**
   * Enable field because we are using one of its source plugins.
   *
   * @var array
   */
  protected static $modules = ['migrate', 'field'];

  /**
   * Tests Migration::set().
   */
  public function testSetInvalidation(): void {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([
      'source' => ['plugin' => 'empty'],
      'destination' => ['plugin' => 'entity:entity_view_mode'],
    ]);
    $this->assertEquals('empty', $migration->getSourcePlugin()->getPluginId());
    $this->assertEquals('entity:entity_view_mode', $migration->getDestinationPlugin()->getPluginId());

    // Test the source plugin is invalidated.
    $migration->set('source', ['plugin' => 'embedded_data', 'data_rows' => [], 'ids' => []]);
    $this->assertEquals('embedded_data', $migration->getSourcePlugin()->getPluginId());

    // Test the destination plugin is invalidated.
    $migration->set('destination', ['plugin' => 'null']);
    $this->assertEquals('null', $migration->getDestinationPlugin()->getPluginId());
  }

}
