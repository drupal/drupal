<?php

namespace Drupal\Tests\tracker\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Core\Database\Database;

/**
 * Tests migration of tracker_node.
 *
 * @group tracker
 */
class MigrateTrackerNodeTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'text',
    'tracker',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('tracker', ['tracker_node', 'tracker_user']);

    $this->executeMigrations([
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node',
      'd7_tracker_node',
    ]);
  }

  /**
   * Tests migration of tracker node table.
   */
  public function testMigrateTrackerNode() {
    $connection = Database::getConnection('default', 'migrate');
    $num_rows = $connection
        ->select('tracker_node', 'tn')
        ->fields('tn', ['nid', 'published', 'changed'])
        ->countQuery()
        ->execute()
        ->fetchField();
    $this->assertIdentical('1', $num_rows);

    $tracker_nodes = $connection
        ->select('tracker_node', 'tn')
        ->fields('tn', ['nid', 'published', 'changed'])
        ->execute();
    $row = $tracker_nodes->fetchAssoc();
    $this->assertIdentical('1', $row['nid']);
    $this->assertIdentical('1', $row['published']);
    $this->assertIdentical('1421727536', $row['changed']);
  }

}
