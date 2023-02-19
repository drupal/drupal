<?php

namespace Drupal\Tests\file\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests file_post_update_add_permissions_to_roles().
 *
 * @group file
 * @group legacy
 *
 * @see file_post_update_add_permissions_to_roles()
 */
class FileAddPermissionsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests adding 'delete own files' permission.
   */
  public function testUpdate(): void {
    $roles = Role::loadMultiple();
    $this->assertGreaterThan(2, count($roles));
    foreach ($roles as $role) {
      $permissions = $role->toArray()['permissions'];
      $this->assertNotContains('delete own files', $permissions);
    }

    $this->runUpdates();

    $role = Role::load(Role::ANONYMOUS_ID);
    $permissions = $role->toArray()['permissions'];
    $this->assertNotContains('delete own files', $permissions);

    $role = Role::load(Role::AUTHENTICATED_ID);
    $permissions = $role->toArray()['permissions'];
    $this->assertContains('delete own files', $permissions);

    // Admin roles have the permission and do not need assigned.
    $role = Role::load('administrator');
    $permissions = $role->toArray()['permissions'];
    $this->assertNotContains('delete own files', $permissions);
  }

}
