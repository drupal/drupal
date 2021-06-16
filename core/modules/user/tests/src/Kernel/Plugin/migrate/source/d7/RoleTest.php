<?php

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d7_user_role source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d7\Role
 * @group user
 */
class RoleTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal', 'user'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $expected = [
      [
        'rid' => 1,
        'name' => 'anonymous user',
        'permissions' => [
          'access content',
        ],
      ],
      [
        'rid' => 2,
        'name' => 'authenticated user',
        'permissions' => [
          'access comments',
          'access content',
          'post comments',
          'post comments without approval',
        ],
      ],
      [
        'rid' => 3,
        'name' => 'administrator',
        'permissions' => [
          'access comments',
          'administer comments',
          'post comments',
          'post comments without approval',
          'access content',
          'administer content types',
          'administer nodes',
        ],
      ],
    ];
    $data = [
      [[], $expected],
    ];
    foreach ($expected as $row) {
      foreach ($row['permissions'] as $permission) {
        $data[0][0]['role_permission'][] = [
          'permission' => $permission,
          'rid' => $row['rid'],
        ];
      }
      unset($row['permissions']);
      $data[0][0]['role'][] = $row;
    }
    return $data;
  }

}
