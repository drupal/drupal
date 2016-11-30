<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests draggable list builder.
 *
 * @group config
 */
class ConfigDraggableListBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('config_test');

  /**
   * Test draggable lists.
   */
  public function testDraggableList() {
    $this->drupalLogin($this->drupalCreateUser(array('administer permissions')));

    // Create more than 50 roles.
    for ($i = 0; $i < 51; $i++) {
      $role = Role::create([
        'id' => 'role_' . $i,
        'label' => "Role $i",
      ]);
      $role->save();
    }

    // Navigate to Roles page
    $this->drupalGet('admin/people/roles');

    // Test for the page title.
    $this->assertSession()->titleEquals(t('Roles') . ' | Drupal');

    // Count the number of rows in table.
    $rows = $this->xpath('//form[@class="user-admin-roles-form"]/table/tbody/tr');
    $this->assertGreaterThan(50, count($rows));
    for ($i = 0; $i < 51; $i++) {
      $this->assertSession()->pageTextContains("Role $i");
    }
  }

}
