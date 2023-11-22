<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\migrate\Plugin\MigrationInterface;
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
    $this->startCollectingMessages();
  }

  /**
   * Assert the logged migrate messages.
   *
   * @param string[][] $role_data
   *   An array of role data keyed by the destination role id. The role data
   *   contains the source role id, an array of valid permissions and an array
   *   of invalid permissions.
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map
   *   The migration ID map plugin.
   */
  public function assertMessages(array $role_data, MigrateIdMapInterface $id_map) {
    foreach ($id_map->getMessages() as $message) {
      $permissions = implode("', '", $role_data[$message->dest_id]['invalid']);
      $expected_message = "Permission(s) '" . $permissions . "' not found.";
      $this->assertSame($expected_message, $message->message);
      $this->assertSame(MigrationInterface::MESSAGE_WARNING, (int) $message->level);
    }

  }

  /**
   * Asserts there are no duplicate roles.
   */
  public function assertNoDuplicateRoles() {
    $roles = [
      'anonymous1',
      'authenticated1',
      'administrator1',
      'migrate_test_role_11',
      'migrate_test_role_21',
      'migrate_test_role_3_that_is_longer_than_thirty_two_characters1',
      'migrate_test_role_41',
    ];
    $this->assertEmpty(Role::loadMultiple($roles));
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
    $this->assertSame($permissions, $role->getPermissions());
    $this->assertSame([[$id]], $id_map->lookupDestinationIds(['rid' => $lookupId]));
  }

  /**
   * Helper to assert the user roles.
   *
   * @param array $permissions
   *   Contains the valid and invalid permissions.
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map
   *   The map table plugin.
   *
   * @internal
   */
  protected function assertRoles(array $permissions, MigrateIdMapInterface $id_map): void {
    foreach ($permissions as $rid => $datum) {
      $this->assertRole($rid, $datum['valid'], $datum['rid'], $id_map);
    }
  }

  /**
   * Data provider for user role migration tests.
   */
  public function providerTestUserRole() {
    return [
      'filter only' => [
        'modules' => [],
        'migrations' => [
          'd6_filter_format',
          'd6_user_role',
        ],
        'role_data' => [
          'anonymous' => [
            'rid' => 1,
            'valid' => [
              'access content',
              'use text format filtered_html',
            ],
            'invalid' => [
              'migrate test anonymous permission',
            ],
          ],
          'authenticated' => [
            'rid' => 2,
            'valid' => [
              'access content',
              'use text format filtered_html',
            ],
            'invalid' => [
              'access comments',
              'migrate test authenticated permission',
              'post comments',
              'skip comment approval',
            ],
          ],
          'migrate_test_role_1' => [
            'rid' => 3,
            'valid' => [
              'use text format full_html',
              'use text format php_code',
            ],
            'invalid' => [
              'migrate test role 1 test permission',
            ],
          ],
          'migrate_test_role_2' => [
            'rid' => 4,
            'valid' => [
              'access content overview',
              'administer nodes',
              'use text format php_code',
            ],
            'invalid' => [
              'administer contact forms',
              'create forum content',
              'delete any blog content',
              'delete any forum content',
              'delete own blog content',
              'delete own forum content',
              'edit any blog content',
              'edit any forum content',
              'edit own blog content',
              'edit own forum content',
              'migrate test role 2 test permission',
              'skip comment approval',
              'use PHP for settings',
            ],
          ],
          'migrate_test_role_3_that_is_longer_than_thirty_two_characters' => [
            'rid' => 5,
            'valid' => [
              'use text format php_code',
            ],
            'invalid' => [],
          ],
        ],
      ],
      'all dependent migrations' => [
        'modules' => [
          'block',
          'block_content',
          'comment',
          'contact',
          'config_translation',
          'language',
          'link',
          'menu_ui',
          'node',
          'taxonomy',
          'text',
        ],
        'migrations' => [
          'language',
          'd6_comment_type',
          'block_content_type',
          'contact_category',
          'd6_filter_format',
          'd6_taxonomy_vocabulary',
          'd6_taxonomy_vocabulary_translation',
          'd6_user_role',
        ],
        'role_data' => [
          'anonymous' => [
            'rid' => 1,
            'valid' => [
              'access content',
              'use text format filtered_html',
            ],
            'invalid' => [
              'migrate test anonymous permission',
            ],
          ],
          'authenticated' => [
            'rid' => 2,
            'valid' => [
              'access comments',
              'access content',
              'post comments',
              'skip comment approval',
              'use text format filtered_html',
            ],
            'invalid' => [
              'migrate test authenticated permission',
            ],
          ],
          'migrate_test_role_1' => [
            'rid' => 3,
            'valid' => [
              'use text format full_html',
              'use text format php_code',
            ],
            'invalid' => [
              'migrate test role 1 test permission',
            ],
          ],
          'migrate_test_role_2' => [
            'rid' => 4,
            'valid' => [
              'access content overview',
              'administer contact forms',
              'administer nodes',
              'create forum content',
              'delete any forum content',
              'delete own forum content',
              'edit any forum content',
              'edit own forum content',
              'skip comment approval',
              'use text format php_code',
            ],
            'invalid' => [
              'delete any blog content',
              'delete own blog content',
              'edit any blog content',
              'edit own blog content',
              'migrate test role 2 test permission',
              'use PHP for settings',
            ],
          ],
          'migrate_test_role_3_that_is_longer_than_thirty_two_characters' => [
            'rid' => 5,
            'valid' => [
              'use text format php_code',
            ],
            'invalid' => [],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests user role migration.
   *
   * @param string[] $modules
   *   A list of modules to install.
   * @param string[] $migrations
   *   A list of migrations to execute.
   * @param string[][] $role_data
   *   An array of role data keyed by the destination role id. The role data
   *   contains the source role id, an array of valid permissions and an array
   *   of invalid permissions.
   *
   * @dataProvider providerTestUserRole
   */
  public function testUserRole(array $modules, array $migrations, array $role_data) {
    if ($modules) {
      // Install modules that have migrations that may provide permissions.
      \Drupal::service('module_installer')->install($modules);
      $this->installEntitySchema('block_content');
      $this->installConfig(['block_content', 'comment']);
      $this->migrateContentTypes();
    }
    $this->executeMigrations($migrations);
    $id_map = $this->getMigration('d6_user_role')->getIdMap();

    // After all the migrations are run, there are changes to the permissions.
    $this->assertRoles($role_data, $id_map);

    $roles = [
      'anonymous1',
      'authenticated1',
      'administrator1',
      'migrate_test_role_11',
      'migrate_test_role_21',
      'migrate_test_role_3_that_is_longer_than_thirty_two_characters1',
    ];
    $this->assertEmpty(Role::loadMultiple($roles));

    $this->assertMessages($role_data, $id_map);
    $this->assertSame(4, $id_map->messageCount());

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
    $this->assertNoDuplicateRoles();

    // Test that the existing roles have not changed.
    $this->assertRoles($role_data, $id_map);

    // Test the migration of the new role, migrate_test_role_4.
    $permissions = ['access content'];
    $this->assertRole('migrate_test_role_4', $permissions, 6, $id_map);
  }

}
