<?php

/**
 * @file
 * Contains Drupal\system\Tests\Upgrade\FieldUIUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Test upgrade of Field UI.
 */
class FieldUIUpgradePathTest extends UpgradePathTestBase {

  protected $normal_role_id = 4;
  protected $normal_role_name = 'Normal role';
  protected $admin_role_id = 5;
  protected $admin_role_name = 'Admin role';

  public static function getInfo() {
    return array(
      'name' => 'Field UI upgrade test',
      'description' => 'Upgrade tests for Field UI.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.filled.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.field_ui.database.php',
    );
    parent::setUp();
  }

 /**
   * Tests Field UI permissions upgrade path.
   *
   * Test that after upgrade users who have the 'administer comments',
   * 'administer content types', 'administer users', and 'administer taxonomy'
   * permission still have access to the manage field and display screens of
   * those entities.
   */
  function testFieldUIPermissions() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    $permissions = array(
      'administer comments' => array(
         'administer comment fields',
         'administer comment display',
       ),
      'administer content types' => array(
         'administer node fields',
         'administer node display',
       ),
      'administer users' => array(
         'administer user fields',
         'administer user display',
       ),
      'administer taxonomy' => array(
         'administer taxonomy_term fields',
         'administer taxonomy_term display',
       ),
    );

    $role_permissions = user_role_permissions(array($this->normal_role_id, $this->admin_role_id));
    foreach ($permissions as $old_permission => $new_permissions) {
      $this->assertFalse(in_array($old_permission, $role_permissions[$this->normal_role_id]), format_string('%role_name does not have the old %permission permission', array('%role_name' => $this->normal_role_name, '%permission' => $old_permission)));
      $this->assertTrue(in_array($old_permission, $role_permissions[$this->admin_role_id]), format_string('%role_name still has the old %permission permission', array('%role_name' => $this->admin_role_name, '%permission' => $old_permission)));
      foreach ($new_permissions as $new_permission) {
        $this->assertFalse(in_array($new_permission, $role_permissions[$this->normal_role_id]), format_string('%role_name does not have the new %permission permission', array('%role_name' => $this->normal_role_name, '%permission' => $new_permission)));
        $this->assertTrue(in_array($new_permission, $role_permissions[$this->admin_role_id]), format_string('%role_name has the new %permission permission', array('%role_name' => $this->admin_role_name, '%permission' => $new_permission)));
      }
    }
  }
}
