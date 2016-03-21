<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Kernel\MigrationTest.
 */

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\Migration;

/**
 * Tests the migration plugin.
 *
 * @group migrate
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\Migration
 */
class MigrationTest extends KernelTestBase {

  /**
   * Enable field because we are using one of its source plugins.
   *
   * @var array
   */
  public static $modules = ['migrate', 'field'];

  /**
   * Tests Migration::set().
   *
   * @covers ::set
   */
  public function testSetInvalidation() {
    $migration = new Migration([], uniqid(), [
      'source' => ['plugin' => 'empty'],
      'destination' => ['plugin' => 'entity:entity_view_mode'],
    ]);
    $this->assertEqual('empty', $migration->getSourcePlugin()->getPluginId());
    $this->assertEqual('entity:entity_view_mode', $migration->getDestinationPlugin()->getPluginId());

    // Test the source plugin is invalidated.
    $migration->set('source', ['plugin' => 'd6_field']);
    $this->assertEqual('d6_field', $migration->getSourcePlugin()->getPluginId());

    // Test the destination plugin is invalidated.
    $migration->set('destination', ['plugin' => 'null']);
    $this->assertEqual('null', $migration->getDestinationPlugin()->getPluginId());
  }

}
