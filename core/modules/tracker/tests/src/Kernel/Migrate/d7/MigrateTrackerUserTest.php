<?php

namespace Drupal\Tests\tracker\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
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
  protected static $modules = [
    'menu_ui',
    'node',
    'text',
    'tracker',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('tracker', ['tracker_node', 'tracker_user']);

    $this->migrateContentTypes();
    $this->migrateUsers(FALSE);
    $this->executeMigrations([
      'd7_node',
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
    $this->assertSame('1', $num_rows);

    $tracker_nodes = $connection
      ->select('tracker_user', 'tu')
      ->fields('tu', ['nid', 'uid', 'published', 'changed'])
      ->execute();
    $row = $tracker_nodes->fetchAssoc();
    $this->assertSame('1', $row['nid']);
    $this->assertSame('2', $row['uid']);
    $this->assertSame('1', $row['published']);
    $this->assertSame('1421727536', $row['changed']);
  }

}
