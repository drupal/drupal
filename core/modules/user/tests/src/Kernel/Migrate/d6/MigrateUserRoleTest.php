<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Upgrade user roles to user.role.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateUserRoleTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations(['d6_filter_format', 'd6_user_role']);
  }

  /**
   * Helper function to perform assertions on a user role.
   *
   * @param string $id
   *   The role ID.
   * @param string[] $permissions
   *   An array of user permissions.
   * @param int $lookupId
   *   The original numeric ID of the role in the source database.
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map
   *   The map table plugin.
   *
   * @internal
   */
  protected function assertRole(string $id, array $permissions, int $lookupId, MigrateIdMapInterface $id_map): void {
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($id);
    $this->assertInstanceOf(RoleInterface::class, $role);
    sort($permissions);
    $this->assertSame($permissions, $role->getPermissions());
    $this->assertSame([[$id]], $id_map->lookupDestinationIds(['rid' => $lookupId]));
  }

  /**
   * Helper function to test the migration of the user roles. The user roles
   * will be re-imported and the tests here will be repeated.
   *
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map
   *   The map table plugin.
   *
   * @internal
   */
  protected function assertRoles(MigrateIdMapInterface $id_map): void {

    // The permissions for each role are found in the two tables in the Drupal 6
    // source database. One is the permission table and the other is the
    // filter_format table.
    $permissions = [
      // From permission table.
      'access content',
      'migrate test anonymous permission',
      // From filter_format tables.
      'use text format filtered_html',
    ];
    $this->assertRole('anonymous', $permissions, 1, $id_map);

    $permissions = [
      // From permission table.
      'access comments',
      'access content',
      'migrate test authenticated permission',
      'post comments',
      'skip comment approval',
      // From filter_format.
      'use text format filtered_html',
    ];
    $this->assertRole('authenticated', $permissions, 2, $id_map);

    $permissions = [
      // From permission table.
      'migrate test role 1 test permission',
      // From filter format.
      'use text format full_html',
      'use text format php_code',
    ];
    $this->assertRole('migrate_test_role_1', $permissions, 3, $id_map);

    $permissions = [
      // From permission table.
      'migrate test role 2 test permission',
      'use PHP for settings',
      'administer contact forms',
      'skip comment approval',
      'edit own blog content',
      'edit any blog content',
      'delete own blog content',
      'delete any blog content',
      'create forum content',
      'delete any forum content',
      'delete own forum content',
      'edit any forum content',
      'edit own forum content',
      'administer nodes',
      'access content overview',
      // From filter format.
      'use text format php_code',
    ];
    $this->assertRole('migrate_test_role_2', $permissions, 4, $id_map);

    // The only permission for this role is a filter format.
    $permissions = ['use text format php_code'];
    $this->assertRole('migrate_test_role_3_that_is_longer_than_thirty_two_characters', $permissions, 5, $id_map);
  }

  /**
   * Tests user role migration.
   */
  public function testUserRole() {
    $id_map = $this->getMigration('d6_user_role')->getIdMap();
    $this->assertRoles($id_map);

    // Test there are no duplicated roles.
    $roles = [
      'anonymous1',
      'authenticated1',
      'administrator1',
      'migrate_test_role_11',
      'migrate_test_role_21',
      'migrate_test_role_3_that_is_longer_than_thirty_two_characters1',
    ];
    $this->assertEmpty(Role::loadMultiple($roles));

    // Remove the map row for the migrate_test_role_1 role and rerun the
    // migration. This will re-import the migrate_test_role_1 role migration
    // again.
    $this->sourceDatabase->insert('role')
      ->fields([
        'rid' => 6,
        'name' => 'migrate test role 4',
      ])
      ->execute();
    $this->sourceDatabase->insert('permission')
      ->fields([
        'pid' => 7,
        'rid' => 6,
        'perm' => 'access content',
        'tid' => 0,
      ])
      ->execute();

    $id_map->delete(['rid' => 3]);

    $this->executeMigration('d6_user_role');

    // Test there are no duplicated roles.
    $roles[] = 'migrate_test_role_41';
    $this->assertEmpty(Role::loadMultiple($roles));

    // Test that the existing roles have not changed.
    $this->assertRoles($id_map);

    // Test the migration of the new role, migrate_test_role_4.
    $permissions = ['access content'];
    $this->assertRole('migrate_test_role_4', $permissions, 6, $id_map);
  }

}
