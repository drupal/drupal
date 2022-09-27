<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\Role;

/**
 * Verify that role permissions can be added and removed via the permissions
 * pages.
 *
 * @group user
 */
class UserPermissionsTest extends BrowserTestBase {

  /**
   * User with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User's role ID.
   *
   * @var string
   */
  protected $rid;

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
      'access user profiles',
      'administer site configuration',
      'administer modules',
      'administer account settings',
    ]);

    // Find the new role ID.
    $all_rids = $this->adminUser->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    $this->rid = reset($all_rids);
  }

  /**
   * Tests changing user permissions through the permissions pages.
   */
  public function testUserPermissionChanges() {
    $permissions_hash_generator = $this->container->get('user_permissions_hash_generator');

    $storage = $this->container->get('entity_type.manager')->getStorage('user_role');

    // Create an additional role and mark it as admin role.
    Role::create(['is_admin' => TRUE, 'id' => 'administrator', 'label' => 'Administrator'])->save();
    $storage->resetCache();

    $this->drupalLogin($this->adminUser);
    $rid = $this->rid;
    $account = $this->adminUser;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertSame($previous_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));

    // Add a permission.
    $this->assertFalse($account->hasPermission('administer users'), 'User does not have "administer users" permission.');
    $edit = [];
    $edit[$rid . '[administer users]'] = TRUE;
    $this->drupalGet('admin/people/permissions');
    $this->submitForm($edit, 'Save permissions');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $storage->resetCache();
    $this->assertTrue($account->hasPermission('administer users'), 'User now has "administer users" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertSame($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertNotEquals($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
    $previous_permissions_hash = $current_permissions_hash;

    // Remove a permission.
    $this->assertTrue($account->hasPermission('access user profiles'), 'User has "access user profiles" permission.');
    $edit = [];
    $edit[$rid . '[access user profiles]'] = FALSE;
    $this->drupalGet('admin/people/permissions');
    $this->submitForm($edit, 'Save permissions');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $storage->resetCache();
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertSame($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertNotEquals($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');

    // Permissions can be changed using the module-specific pages with the same
    // result.
    $edit = [];
    $edit[$rid . '[access user profiles]'] = TRUE;
    $this->drupalGet('admin/people/permissions/module/user');
    $this->submitForm($edit, 'Save permissions');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $storage->resetCache();
    $this->assertTrue($account->hasPermission('access user profiles'), 'User again has "access user profiles" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertSame($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertEquals($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has reverted.');

    // Ensure that the admin role doesn't have any checkboxes.
    $this->drupalGet('admin/people/permissions');
    foreach (array_keys($this->container->get('user.permissions')->getPermissions()) as $permission) {
      $this->assertSession()->checkboxChecked('administrator[' . $permission . ']');
      $this->assertSession()->fieldDisabled('administrator[' . $permission . ']');
    }
  }

  /**
   * Tests assigning of permissions for the administrator role.
   */
  public function testAdministratorRole() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people/role-settings');

    // Verify that the administration role is none by default.
    $this->assertTrue($this->assertSession()->optionExists('edit-user-admin-role', '')->isSelected());

    $this->assertFalse(Role::load($this->rid)->isAdmin());

    // Set the user's role to be the administrator role.
    $edit = [];
    $edit['user_admin_role'] = $this->rid;
    $this->drupalGet('admin/people/role-settings');
    $this->submitForm($edit, 'Save configuration');

    \Drupal::entityTypeManager()->getStorage('user_role')->resetCache();
    $this->assertTrue(Role::load($this->rid)->isAdmin());

    // Enable block module and ensure the 'administer news feeds'
    // permission is assigned by default.
    \Drupal::service('module_installer')->install(['block']);

    $this->assertTrue($this->adminUser->hasPermission('administer blocks'), 'The permission was automatically assigned to the administrator role');

    // Ensure that selecting '- None -' removes the admin role.
    $edit = [];
    $edit['user_admin_role'] = '';
    $this->drupalGet('admin/people/role-settings');
    $this->submitForm($edit, 'Save configuration');

    \Drupal::entityTypeManager()->getStorage('user_role')->resetCache();
    \Drupal::configFactory()->reset();
    $this->assertFalse(Role::load($this->rid)->isAdmin());

    // Manually create two admin roles, in that case the single select should be
    // hidden.
    Role::create(['id' => 'admin_role_0', 'is_admin' => TRUE, 'label' => 'Admin role 0'])->save();
    Role::create(['id' => 'admin_role_1', 'is_admin' => TRUE, 'label' => 'Admin role 1'])->save();
    $this->drupalGet('admin/people/role-settings');
    $this->assertSession()->fieldNotExists('user_admin_role');
  }

  /**
   * Verify proper permission changes by user_role_change_permissions().
   */
  public function testUserRoleChangePermissions() {
    $permissions_hash_generator = $this->container->get('user_permissions_hash_generator');

    $rid = $this->rid;
    $account = $this->adminUser;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);

    // Verify current permissions.
    $this->assertFalse($account->hasPermission('administer users'), 'User does not have "administer users" permission.');
    $this->assertTrue($account->hasPermission('access user profiles'), 'User has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User has "administer site configuration" permission.');

    // Change permissions.
    $permissions = [
      'administer users' => 1,
      'access user profiles' => 0,
    ];
    user_role_change_permissions($rid, $permissions);

    // Verify proper permission changes.
    $this->assertTrue($account->hasPermission('administer users'), 'User now has "administer users" permission.');
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User still has "administer site configuration" permission.');

    // Verify the permissions hash has changed.
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertNotEquals($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
  }

  /**
   * Verify 'access content' is listed in the correct location.
   */
  public function testAccessContentPermission() {
    $this->drupalLogin($this->adminUser);

    // When Node is not installed the 'access content' permission is listed next
    // to 'access site reports'.
    $this->drupalGet('admin/people/permissions');
    $next_row = $this->xpath('//tr[@data-drupal-selector=\'edit-permissions-access-content\']/following-sibling::tr[1]');
    $this->assertEquals('edit-permissions-access-site-reports', $next_row[0]->getAttribute('data-drupal-selector'));

    // When Node is installed the 'access content' permission is listed next to
    // to 'view own unpublished content'.
    \Drupal::service('module_installer')->install(['node']);
    $this->drupalGet('admin/people/permissions');
    $next_row = $this->xpath('//tr[@data-drupal-selector=\'edit-permissions-access-content\']/following-sibling::tr[1]');
    $this->assertEquals('edit-permissions-view-own-unpublished-content', $next_row[0]->getAttribute('data-drupal-selector'));
  }

  /**
   * Verify that module-specific pages have correct access.
   */
  public function testAccessModulePermission() {
    $this->drupalLogin($this->adminUser);

    // When Node is not installed, the node-permissions page is not available.
    $this->drupalGet('admin/people/permissions/module/node');
    $this->assertSession()->statusCodeEquals(403);

    // Modules that do not create permissions have no permissions pages.
    \Drupal::service('module_installer')->install(['automated_cron']);
    $this->drupalGet('admin/people/permissions/module/automated_cron');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/people/permissions/module/node,automated_cron');
    $this->assertSession()->statusCodeEquals(403);

    // When Node is installed, the node-permissions page is available.
    \Drupal::service('module_installer')->install(['node']);
    $this->drupalGet('admin/people/permissions/module/node');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/people/permissions/module/node,automated_cron');
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous users cannot access any of these pages.
    $this->drupalLogout();
    $this->drupalGet('admin/people/permissions/module/node');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/people/permissions/module/automated_cron');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/people/permissions/module/node,automated_cron');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Verify that bundle-specific pages work properly.
   */
  public function testAccessBundlePermission() {
    $this->drupalLogin($this->adminUser);

    \Drupal::service('module_installer')->install(['block_content', 'taxonomy']);
    $this->grantPermissions(Role::load($this->rid), ['administer blocks', 'administer taxonomy']);

    // Bundles that do not have permissions have no permissions pages.
    $edit = [];
    $edit['label'] = 'Test block type';
    $edit['id'] = 'test_block_type';
    $this->drupalGet('admin/structure/block/block-content/types/add');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/block/block-content/manage/test_block_type/permissions');
    $this->assertSession()->statusCodeEquals(403);

    // Permissions can be changed using the bundle-specific pages.
    $edit = [];
    $edit['name'] = 'Test vocabulary';
    $edit['vid'] = 'test_vocabulary';
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->submitForm($edit, 'Save');

    $this->drupalGet('admin/structure/taxonomy/manage/test_vocabulary/overview/permissions');
    $this->assertSession()->checkboxNotChecked('authenticated[create terms in test_vocabulary]');
    $this->assertSession()->fieldExists('authenticated[create terms in test_vocabulary]')->check();
    $this->getSession()->getPage()->pressButton('Save permissions');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxChecked('authenticated[create terms in test_vocabulary]');

    // Typos produce 404 response, not server errors.
    $this->drupalGet('admin/structure/taxonomy/manage/test_typo/overview/permissions');
    $this->assertSession()->statusCodeEquals(404);

    // Anonymous users cannot access any of these pages.
    $this->drupalLogout();
    $this->drupalGet('admin/structure/taxonomy/manage/test_vocabulary/overview/permissions');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/structure/block/block-content/manage/test_block_type/permissions');
    $this->assertSession()->statusCodeEquals(403);
  }

}
