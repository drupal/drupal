<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests adding, editing and deleting user roles and changing role weights.
 *
 * @group user
 */
class UserRoleAdminTest extends BrowserTestBase {

  /**
   * User with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'administer permissions',
      'administer users',
    ]);
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'test_role_admin_test_local_tasks_block']);
  }

  /**
   * Tests adding, renaming and deleting roles.
   */
  public function testRoleAdministration() {
    $this->drupalLogin($this->adminUser);
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    // Test presence of tab.
    $this->drupalGet('admin/people/permissions');
    $this->assertSession()->elementsCount('xpath', '//div[@id="block-test-role-admin-test-local-tasks-block"]/ul/li/a[contains(., "Roles")]', 1);

    // Test adding a role. (In doing so, we use a role name that happens to
    // correspond to an integer, to test that the role administration pages
    // correctly distinguish between role names and IDs.)
    $role_name = '123';
    $edit = ['label' => $role_name, 'id' => $role_name];
    $this->drupalGet('admin/people/roles/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Role 123 has been added.");
    $role = Role::load($role_name);
    $this->assertIsObject($role);

    // Check that the role was created in site default language.
    $this->assertEquals($default_langcode, $role->language()->getId());

    // Verify permissions local task can be accessed when editing a role.
    $this->drupalGet("admin/people/roles/manage/{$role->id()}");
    $local_tasks_block = $this->assertSession()->elementExists('css', '#block-test-role-admin-test-local-tasks-block');
    $local_tasks_block->clickLink('Permissions');
    $this->assertSession()->fieldExists("{$role->id()}[change own username]");

    // Try adding a duplicate role.
    $this->drupalGet('admin/people/roles/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("The machine-readable name is already in use. It must be unique.");

    // Test renaming a role.
    $role_name = '456';
    $edit = ['label' => $role_name];
    $this->drupalGet("admin/people/roles/manage/{$role->id()}");
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Role {$role_name} has been updated.");
    \Drupal::entityTypeManager()->getStorage('user_role')->resetCache([$role->id()]);
    $new_role = Role::load($role->id());
    $this->assertEquals($role_name, $new_role->label(), 'The role name has been successfully changed.');

    // Test deleting a role.
    $this->drupalGet("admin/people/roles/manage/{$role->id()}");
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("Role {$role_name} has been deleted.");
    $this->assertSession()->linkByHrefNotExists("admin/people/roles/manage/{$role->id()}", 'Role edit link removed.');
    \Drupal::entityTypeManager()->getStorage('user_role')->resetCache([$role->id()]);
    $this->assertNull(Role::load($role->id()), 'A deleted role can no longer be loaded.');

    // Make sure that the system-defined roles can be edited via the user
    // interface.
    $this->drupalGet('admin/people/roles/manage/' . RoleInterface::ANONYMOUS_ID);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Delete role');
    $this->drupalGet('admin/people/roles/manage/' . RoleInterface::AUTHENTICATED_ID);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Delete role');
  }

  /**
   * Tests user role weight change operation and ordering.
   */
  public function testRoleWeightOrdering() {
    $this->drupalLogin($this->adminUser);
    $roles = Role::loadMultiple();
    $weight = count($roles);
    $new_role_weights = [];
    $saved_rids = [];

    // Change the role weights to make the roles in reverse order.
    $edit = [];
    foreach ($roles as $role) {
      $edit['entities[' . $role->id() . '][weight]'] = $weight;
      $new_role_weights[$role->id()] = $weight;
      $saved_rids[] = $role->id();
      $weight--;
    }
    $this->drupalGet('admin/people/roles');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The role settings have been updated.');

    // Load up the user roles with the new weights.
    $roles = Role::loadMultiple();
    $rids = [];
    // Test that the role weights have been correctly saved.
    foreach ($roles as $role) {
      $this->assertEquals($role->getWeight(), $new_role_weights[$role->id()]);
      $rids[] = $role->id();
    }
    // The order of the roles should be reversed.
    $this->assertSame(array_reverse($saved_rids), $rids);
  }

}
