<?php

namespace Drupal\Tests\user\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests user permissions sort upgrade path.
 *
 * @group Update
 */
class UserUpdateOrderPermissionsTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that permissions are ordered by machine name.
   */
  public function testPermissionsOrder() {
    $authenticated = Role::load('authenticated');
    $permissions = $authenticated->getPermissions();
    sort($permissions);
    $this->assertNotIdentical($permissions, $authenticated->getPermissions());

    $this->runUpdates();
    $authenticated = Role::load('authenticated');
    $this->assertIdentical($permissions, $authenticated->getPermissions());
  }

}
