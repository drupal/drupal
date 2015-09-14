<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateNodeBuilderTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\migrate\Entity\Migration;

/**
 * @group migrate_drupal_6
 */
class MigrateNodeBuilderTest extends MigrateDrupal6TestBase {

  public static $modules = ['migrate', 'migrate_drupal', 'node'];

  /**
   * @var MigrationInterface[]
   */
  protected $builtMigrations = [];

  /**
   * Asserts various aspects of a migration entity.
   *
   * @param string $id
   *   The migration ID.
   * @param string $label
   *   The label.
   */
  protected function assertEntity($id, $label) {
    $migration = $this->builtMigrations[$id];
    $this->assertTrue($migration instanceof Migration);
    $this->assertIdentical($id, $migration->id());
    $this->assertEqual($label, $migration->label());
  }

  /**
   * Tests creating migrations from a template, using a builder plugin.
   */
  public function testCreateMigrations() {
    $templates = [
      'd6_node' => [
        'id' => 'd6_node',
        'label' => 'Drupal 6 nodes',
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
    // Key the array.
    foreach ($migrations as $migration) {
      $this->builtMigrations[$migration->id()] = $migration;
    }
    $this->assertIdentical(11, count($this->builtMigrations));
    $this->assertEntity('d6_node__article', 'Drupal 6 nodes (article)');
    $this->assertEntity('d6_node__company', 'Drupal 6 nodes (company)');
    $this->assertEntity('d6_node__employee', 'Drupal 6 nodes (employee)');
    $this->assertEntity('d6_node__event', 'Drupal 6 nodes (event)');
    $this->assertEntity('d6_node__page', 'Drupal 6 nodes (page)');
    $this->assertEntity('d6_node__sponsor', 'Drupal 6 nodes (sponsor)');
    $this->assertEntity('d6_node__story', 'Drupal 6 nodes (story)');
    $this->assertEntity('d6_node__test_event', 'Drupal 6 nodes (test_event)');
    $this->assertEntity('d6_node__test_page', 'Drupal 6 nodes (test_page)');
    $this->assertEntity('d6_node__test_planet', 'Drupal 6 nodes (test_planet)');
    $this->assertEntity('d6_node__test_story', 'Drupal 6 nodes (test_story)');
  }

}
