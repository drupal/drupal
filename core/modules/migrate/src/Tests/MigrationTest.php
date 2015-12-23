<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\MigrationTest.
 */

namespace Drupal\migrate\Tests;

use Drupal\migrate\Entity\Migration;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the migration entity.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate\Entity\Migration
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
   * @covers ::set()
   */
  public function testSetInvalidation() {
    $migration = Migration::create([
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
