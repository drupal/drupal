<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\migrate\Plugin\MigrationInterface;
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
   * @param string[] $permissions
   *   The expected permissions.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $label, array $permissions): void {
    /** @var \Drupal\user\RoleInterface $entity */
    $entity = Role::load($id);
    $this->assertInstanceOf(RoleInterface::class, $entity);
    $this->assertSame($label, $entity->label());
    $this->assertSame($permissions, $entity->getPermissions());
  }

  /**
   * Tests user role migration.
   */
  public function testUserRole() {
    $anonymous_permissions = ['access content'];
    $this->assertEntity('anonymous', 'anonymous user', $anonymous_permissions);
    $this->assertEntity('authenticated', 'authenticated user', $anonymous_permissions);
    $admin_permissions = [
      'access administration pages',
      'access content',
      'access site in maintenance mode',
      'access site reports',
      'access user profiles',
      'administer menu',
      'administer modules',
      'administer permissions',
      'administer site configuration',
      'administer software updates',
      'administer themes',
      'administer users',
      'cancel account',
      'change own username',
      'select account cancellation method',
      'view the administration theme',
    ];
    $this->assertEntity('administrator', 'administrator', $admin_permissions);

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
    $this->assertEntity('administrator', 'administrator', $admin_permissions);
    $this->assertEntity('anonymous', 'anonymous user', $anonymous_permissions);
    $this->assertEntity('authenticated', 'authenticated user', $anonymous_permissions);

    // Test the migration of the new role, test role.
    $this->assertEntity('test_role', 'test role', $anonymous_permissions);

    // Tests the migration log contains an error message.
    // User role Authenticated.
    $permissions[1] = [
      'access comments',
      'use text format filtered_html',
    ];
    // User role test_role.
    $permissions[2] = [
      'access comments',
      'post comments',
      'skip comment approval',
      'use text format custom_text_format',
      'use text format filtered_html',
    ];
    // User role administrator.
    $permissions[3] = [
      'access all views',
      'access comments',
      'access content overview',
      'access contextual links',
      'access news feeds',
      'access printer-friendly version',
      'access site-wide contact form',
      'access statistics',
      'access toolbar',
      'access user contact forms',
      'add content to books',
      'administer actions',
      'administer blocks',
      'administer book outlines',
      'administer comments',
      'administer contact forms',
      'administer content types',
      'administer fields',
      'administer filters',
      'administer forums',
      'administer image styles',
      'administer languages',
      'administer news feeds',
      'administer nodes',
      'administer search',
      'administer shortcuts',
      'administer statistics',
      'administer taxonomy',
      'administer unit tests',
      'administer url aliases',
      'administer views',
      'block IP addresses',
      'bypass node access',
      'create article content',
      'create new books',
      'create page content',
      'create url aliases',
      'customize shortcut links',
      'delete any article content',
      'delete any page content',
      'delete own article content',
      'delete own page content',
      'delete revisions',
      'delete terms in 1',
      'edit any article content',
      'edit any page content',
      'edit own article content',
      'edit own comments',
      'edit own page content',
      'edit terms in 1',
      'post comments',
      'revert revisions',
      'search content',
      'skip comment approval',
      'switch shortcut sets',
      'translate admin strings',
      'translate blocks',
      'translate content',
      'translate interface',
      'translate user-defined strings',
      'use PHP for settings',
      'use advanced search',
      'use text format custom_text_format',
      'use text format filtered_html',
      'use text format full_html',
      'view own unpublished content',
      'view post access counter',
      'view revisions',
    ];

    foreach ($id_map->getMessages() as $message) {
      $expected_permissions = implode("', '", $permissions[$message->src_rid]);
      $expected_message = "Permission(s) '" . $expected_permissions . "' not found.";
      $this->assertSame($expected_message, $message->message);
      $this->assertSame(MigrationInterface::MESSAGE_WARNING, (int) $message->level);
    }
    $this->assertSame(3, $id_map->messageCount());
  }

}
