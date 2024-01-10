<?php

namespace Drupal\Tests\help\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests help_post_update_add_permissions_to_roles().
 *
 * @group help
 * @group legacy
 * @group #slow
 *
 * @see help_post_update_add_permissions_to_roles()
 */
class AddPermissionsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      __DIR__ . '/../../fixtures/update/drupal-10.access-help-pages.php',
    ];
  }

  /**
   * Tests adding 'access help pages' permission.
   */
  public function testUpdate(): void {
    $roles = Role::loadMultiple();
    $this->assertGreaterThan(2, count($roles));
    foreach ($roles as $role) {
      $permissions = $role->toArray()['permissions'];
      $this->assertNotContains('access help pages', $permissions);
    }

    $this->runUpdates();

    $role = Role::load(Role::ANONYMOUS_ID);
    $permissions = $role->toArray()['permissions'];
    $this->assertNotContains('access help pages', $permissions);

    $role = Role::load(Role::AUTHENTICATED_ID);
    $permissions = $role->toArray()['permissions'];
    $this->assertNotContains('access help pages', $permissions);

    // Admin roles have the permission and do not need assigned.
    $role = Role::load('administrator');
    $permissions = $role->toArray()['permissions'];
    $this->assertNotContains('access help pages', $permissions);

    $role = Role::load('content_editor');
    $permissions = $role->toArray()['permissions'];
    $this->assertContains('access help pages', $permissions);
  }

}
