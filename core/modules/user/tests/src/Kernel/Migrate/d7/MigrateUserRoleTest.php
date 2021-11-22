<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Core\Database\Database;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Upgrade user roles to user.role.*.yml.
 *
 * @group user
 */
class MigrateUserRoleTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_user_role');
  }

  /**
   * Asserts aspects of a user role config entity.
   *
   * @param string $id
   *   The role ID.
   * @param string $label
   *   The role's expected label.
   * @param int $original_rid
   *   The original (integer) ID of the role, to check permissions.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $label, int $original_rid): void {
    /** @var \Drupal\user\RoleInterface $entity */
    $entity = Role::load($id);
    $this->assertInstanceOf(RoleInterface::class, $entity);
    $this->assertSame($label, $entity->label());

    if (isset($original_rid)) {
      $permissions = Database::getConnection('default', 'migrate')
        ->select('role_permission', 'rp')
        ->fields('rp', ['permission'])
        ->condition('rid', $original_rid)
        ->execute()
        ->fetchCol();
      sort($permissions);
      $this->assertSame($permissions, $entity->getPermissions());
    }
  }

  /**
   * Tests user role migration.
   */
  public function testUserRole() {
    $this->assertEntity('anonymous', 'anonymous user', 1);
    $this->assertEntity('authenticated', 'authenticated user', 2);
    $this->assertEntity('administrator', 'administrator', 3);
    // Test there are no duplicated roles.
    $roles = [
      'anonymous1',
      'authenticated1',
      'administrator1',
    ];
    $this->assertEmpty(Role::loadMultiple($roles));

    // Remove the map row for the administrator role and rerun the migration.
    // This will re-import the administrator role again.
    $id_map = $this->getMigration('d7_user_role')->getIdMap();
    $id_map->delete(['rid' => 3]);

    $this->sourceDatabase->insert('role')
      ->fields([
        'rid' => 4,
        'name' => 'test role',
        'weight' => 10,
      ])
      ->execute();
    $this->sourceDatabase->insert('role_permission')
      ->fields([
        'rid' => 4,
        'permission' => 'access content',
        'module' => 'node',
      ])
      ->execute();
    $this->executeMigration('d7_user_role');

    // Test there are no duplicated roles.
    $roles = [
      'anonymous1',
      'authenticated1',
      'administrator1',
    ];
    $this->assertEmpty(Role::loadMultiple($roles));

    // Test that the existing roles have not changed.
    $this->assertEntity('administrator', 'administrator', 3);
    $this->assertEntity('anonymous', 'anonymous user', 1);
    $this->assertEntity('authenticated', 'authenticated user', 2);

    // Test the migration of the new role, test role.
    $this->assertEntity('test_role', 'test role', 4);
  }

}
