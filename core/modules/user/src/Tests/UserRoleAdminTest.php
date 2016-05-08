<?php

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests adding, editing and deleting user roles and changing role weights.
 *
 * @group user
 */
class UserRoleAdminTest extends WebTestBase {

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
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('administer permissions', 'administer users'));
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test adding, renaming and deleting roles.
   */
  function testRoleAdministration() {
    $this->drupalLogin($this->adminUser);
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    // Test presence of tab.
    $this->drupalGet('admin/people/permissions');
    $tabs = $this->xpath('//ul[@class=:classes and //a[contains(., :text)]]', array(
      ':classes' => 'tabs primary',
      ':text' => t('Roles'),
    ));
    $this->assertEqual(count($tabs), 1, 'Found roles tab');

    // Test adding a role. (In doing so, we use a role name that happens to
    // correspond to an integer, to test that the role administration pages
    // correctly distinguish between role names and IDs.)
    $role_name = '123';
    $edit = array('label' => $role_name, 'id' => $role_name);
    $this->drupalPostForm('admin/people/roles/add', $edit, t('Save'));
    $this->assertRaw(t('Role %label has been added.', array('%label' => 123)));
    $role = Role::load($role_name);
    $this->assertTrue(is_object($role), 'The role was successfully retrieved from the database.');

    // Check that the role was created in site default language.
    $this->assertEqual($role->language()->getId(), $default_langcode);

    // Try adding a duplicate role.
    $this->drupalPostForm('admin/people/roles/add', $edit, t('Save'));
    $this->assertRaw(t('The machine-readable name is already in use. It must be unique.'), 'Duplicate role warning displayed.');

    // Test renaming a role.
    $role_name = '456';
    $edit = array('label' => $role_name);
    $this->drupalPostForm("admin/people/roles/manage/{$role->id()}", $edit, t('Save'));
    $this->assertRaw(t('Role %label has been updated.', array('%label' => $role_name)));
    \Drupal::entityManager()->getStorage('user_role')->resetCache(array($role->id()));
    $new_role = Role::load($role->id());
    $this->assertEqual($new_role->label(), $role_name, 'The role name has been successfully changed.');

    // Test deleting a role.
    $this->drupalGet("admin/people/roles/manage/{$role->id()}");
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The role %label has been deleted.', array('%label' => $role_name)));
    $this->assertNoLinkByHref("admin/people/roles/manage/{$role->id()}", 'Role edit link removed.');
    \Drupal::entityManager()->getStorage('user_role')->resetCache(array($role->id()));
    $this->assertFalse(Role::load($role->id()), 'A deleted role can no longer be loaded.');

    // Make sure that the system-defined roles can be edited via the user
    // interface.
    $this->drupalGet('admin/people/roles/manage/' . RoleInterface::ANONYMOUS_ID);
    $this->assertResponse(200, 'Access granted when trying to edit the built-in anonymous role.');
    $this->assertNoText(t('Delete role'), 'Delete button for the anonymous role is not present.');
    $this->drupalGet('admin/people/roles/manage/' . RoleInterface::AUTHENTICATED_ID);
    $this->assertResponse(200, 'Access granted when trying to edit the built-in authenticated role.');
    $this->assertNoText(t('Delete role'), 'Delete button for the authenticated role is not present.');
  }

  /**
   * Test user role weight change operation and ordering.
   */
  function testRoleWeightOrdering() {
    $this->drupalLogin($this->adminUser);
    $roles = user_roles();
    $weight = count($roles);
    $new_role_weights = array();
    $saved_rids = array();

    // Change the role weights to make the roles in reverse order.
    $edit = array();
    foreach ($roles as $role) {
      $edit['entities[' . $role->id() . '][weight]'] = $weight;
      $new_role_weights[$role->id()] = $weight;
      $saved_rids[] = $role->id();
      $weight--;
    }
    $this->drupalPostForm('admin/people/roles', $edit, t('Save order'));
    $this->assertText(t('The role settings have been updated.'), 'The role settings form submitted successfully.');

    // Load up the user roles with the new weights.
    drupal_static_reset('user_roles');
    $roles = user_roles();
    $rids = array();
    // Test that the role weights have been correctly saved.
    foreach ($roles as $role) {
      $this->assertEqual($role->getWeight(), $new_role_weights[$role->id()]);
      $rids[] = $role->id();
    }
    // The order of the roles should be reversed.
    $this->assertIdentical($rids, array_reverse($saved_rids));
  }

}
