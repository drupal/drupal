<?php

namespace Drupal\Tests\update\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests update_post_update_add_view_update_notifications_permission().
 *
 * @group Update
 * @group legacy
 */
class UpdateAddViewUpdateNotificationsPermissionTest extends UpdatePathTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests that the 'view update notifications' permission is correctly granted.
   */
  public function testViewUpdateNotificationsPermission(): void {
    // Add a new 'Junior Admin' role with the legacy permission we care about.
    $junior_admin = $this->createRole(
      ['administer site configuration'],
      'junior_admin', 'Junior Admin'
    );

    $role = Role::load('junior_admin');
    $this->assertTrue($role->hasPermission('administer site configuration'), 'Junior Admin role has legacy permission.');
    $this->assertFalse($role->hasPermission('view update notifications'), 'Junior Admin role does not have the new permission.');

    $this->runUpdates();

    $role = Role::load('junior_admin');
    $this->assertTrue($role->hasPermission('administer site configuration'), 'Junior Admin role still has the legacy permission.');
    $this->assertTrue($role->hasPermission('view update notifications'), 'Junior Admin role now has the new permission.');
  }

}
