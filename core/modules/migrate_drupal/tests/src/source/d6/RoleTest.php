<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\RoleTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 user role source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class RoleTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\Role';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    // This needs to be the identifier of the actual key: cid for comment, nid
    // for node and so on.
    'source' => array(
      'plugin' => 'd6_user_role',
    ),
  );

  protected $expectedResults = array(
    array(
      'rid' => 1,
      'name' => 'anonymous user',
      'permissions' => array(
        'access content',
      ),
    ),
    array(
      'rid' => 2,
      'name' => 'authenticated user',
      'permissions' => array(
        'access comments',
        'access content',
        'post comments',
        'post comments without approval',
      ),
    ),
    array(
      'rid' => 3,
      'name' => 'administrator',
      'permissions' => array(
        'access comments',
        'administer comments',
        'post comments',
        'post comments without approval',
        'access content',
        'administer content types',
        'administer nodes',
      ),
    ),
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 role source functionality',
      'description' => 'Tests D6 role source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $row) {
      $this->databaseContents['permission'][] = array(
        'perm' => implode(', ', $row['permissions']),
        'rid' => $row['rid'],
      );
      unset($row['permissions']);
      $this->databaseContents['role'][] = $row;
    }
    $this->databaseContents['filter_formats'][] = array(
      'format' => 1,
      'roles' => '',
    );
    parent::setUp();
  }

}

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\Role;

class TestRole extends Role {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
