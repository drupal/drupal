<?php

namespace Drupal\Tests\user\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 role source plugin.
 *
 * @group user
 */
class RoleTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\user\Plugin\migrate\source\d6\Role';

  protected $migrationConfiguration = array(
    'id' => 'test',
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
