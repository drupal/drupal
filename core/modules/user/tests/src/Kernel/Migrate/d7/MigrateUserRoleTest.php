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
  protected function setUp() {
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
   * @param int|null $original_rid
   *   The original (integer) ID of the role, to check permissions.
   */
  protected function assertEntity($id, $label, $original_rid) {
    /** @var \Drupal\user\RoleInterface $entity */
    $entity = Role::load($id);
    $this->assertTrue($entity instanceof RoleInterface);
    $this->assertIdentical($label, $entity->label());

    if (isset($original_rid)) {
      $permissions = Database::getConnection('default', 'migrate')
        ->select('role_permission', 'rp')
        ->fields('rp', ['permission'])
        ->condition('rid', $original_rid)
        ->execute()
        ->fetchCol();
      $this->assertIdentical($permissions, $entity->getPermissions());
    }
  }

  /**
   * Tests user role migration.
   */
  public function testUserRole() {
    $this->assertEntity('anonymous', 'anonymous user', 1);
    $this->assertEntity('authenticated', 'authenticated user', 2);
    $this->assertEntity('administrator', 'administrator', 3);
  }

}
