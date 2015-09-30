<?php

/**
 * @file
 * Contains \Drupal\tracker\Tests\Migrate\d7\MigrateTrackerUserTest.
 */

namespace Drupal\tracker\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;
use Drupal\Core\Database\Database;

/**
 * Tests migration of tracker_user.
 *
 * @group tracker
 */
class MigrateTrackerUserTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference',
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
      'd7_node__test_content_type',
      'd7_tracker_node',
    ]);
  }

  /**
   * Tests migration of tracker user table.
   */
  public function testMigrateTrackerUser() {
    $connection = Database::getConnection('default', 'migrate');
    $num_rows = $connection
        ->select('tracker_user', 'tn')
        ->fields('tu', ['nid', 'uid', 'published', 'changed'])
        ->countQuery()
        ->execute()
        ->fetchField();
    $this->assertIdentical('1', $num_rows);

    $tracker_nodes = $connection
        ->select('tracker_user', 'tu')
        ->fields('tu', ['nid', 'uid', 'published', 'changed'])
        ->execute();
    $row = $tracker_nodes->fetchAssoc();
    $this->assertIdentical('1', $row['nid']);
    $this->assertIdentical('2', $row['uid']);
    $this->assertIdentical('1', $row['published']);
    $this->assertIdentical('1421727536', $row['changed']);
  }

}
