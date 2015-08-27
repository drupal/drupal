<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Plugin\migrate\source\d7\RoleTest.
 */

namespace Drupal\Tests\user\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 role source plugin.
 *
 * @group user
 */
class RoleTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\user\Plugin\migrate\source\d7\Role';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd7_user_role',
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
  protected function setUp() {
    foreach ($this->expectedResults as $row) {
      foreach ($row['permissions'] as $permission) {
        $this->databaseContents['role_permission'][] = array(
          'permission' => $permission,
          'rid' => $row['rid'],
        );
      }
      unset($row['permissions']);
      $this->databaseContents['role'][] = $row;
    }
    parent::setUp();
  }

}
