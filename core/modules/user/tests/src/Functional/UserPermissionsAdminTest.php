<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests adding and removing permissions via the UI.
 *
 * @group user
 */
class UserPermissionsAdminTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests granting and revoking permissions via the UI sorts permissions.
   */
  public function testPermissionsSorting(): void {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
    // Start the role with a permission that is near the end of the alphabet.
    $role->grantPermission('view user email addresses');
    $role->save();

    $this->drupalLogin($this->drupalCreateUser([
      'administer permissions',
    ]));
    $this->drupalGet('admin/people/permissions');

    $this->assertSession()->statusCodeEquals(200);

    // Add a permission that is near the start of the alphabet.
    $this->submitForm([
      'test_role[change own username]' => 1,
    ], 'Save permissions');

    // Check that permissions are sorted alphabetically.
    $storage = \Drupal::entityTypeManager()->getStorage('user_role');
    /** @var \Drupal\user\Entity\Role $role */
    $role = $storage->loadUnchanged($role->id());
    $this->assertEquals([
      'change own username',
      'view user email addresses',
    ], $role->getPermissions());

    // Remove the first permission, resulting in a single permission in the
    // first key of the array.
    $this->submitForm([
      'test_role[change own username]' => 0,
    ], 'Save permissions');
    /** @var \Drupal\user\Entity\Role $role */
    $role = $storage->loadUnchanged($role->id());
    $this->assertEquals([
      'view user email addresses',
    ], $role->getPermissions());
  }

}
