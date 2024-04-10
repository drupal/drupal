<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d6_user_role source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d6\Role
 * @group user
 */
class RoleTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [
      [
        'source_data' => [],
        'expected_data' => [],
      ],
    ];

    $roles = [
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

    // The source data.
    foreach ($roles as $role) {
      $tests[0]['source_data']['permission'][] = [
        'rid' => $role['rid'],
        'perm' => implode(', ', $role['permissions']),
      ];
      unset($role['permissions']);
      $tests[0]['source_data']['role'][] = $role;
    }

    $tests[0]['source_data']['filter_formats'] = [
      [
        'format' => 1,
        'roles' => '',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $roles;

    return $tests;
  }

}
