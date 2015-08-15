<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateNodeBuilderTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * @group node
 */
class MigrateNodeBuilderTest extends MigrateDrupal6TestBase {

  public static $modules = ['migrate', 'migrate_drupal', 'node'];

  /**
   * Tests creating migrations from a template, using a builder plugin.
   */
  public function testCreateMigrations() {
    $templates = [
      'd6_node' => [
        'id' => 'd6_node',
        'builder' => [
          'plugin' => 'd6_node',
        ],
        'source' => [
          'plugin' => 'd6_node',
        ],
        'process' => [
          'nid' => 'nid',
          'vid' => 'vid',
          'uid' => 'uid',
        ],
        'destination' => [
          'plugin' => 'entity:node',
        ],
      ],
    ];

    $migrations = \Drupal::service('migrate.migration_builder')->createMigrations($templates);
    $this->assertIdentical(11, count($migrations));
    $this->assertIdentical('d6_node__article', $migrations[0]->id());
    $this->assertIdentical('d6_node__company', $migrations[1]->id());
    $this->assertIdentical('d6_node__employee', $migrations[2]->id());
    $this->assertIdentical('d6_node__event', $migrations[3]->id());
    $this->assertIdentical('d6_node__page', $migrations[4]->id());
    $this->assertIdentical('d6_node__sponsor', $migrations[5]->id());
    $this->assertIdentical('d6_node__story', $migrations[6]->id());
    $this->assertIdentical('d6_node__test_event', $migrations[7]->id());
    $this->assertIdentical('d6_node__test_page', $migrations[8]->id());
    $this->assertIdentical('d6_node__test_planet', $migrations[9]->id());
    $this->assertIdentical('d6_node__test_story', $migrations[10]->id());
  }

}
