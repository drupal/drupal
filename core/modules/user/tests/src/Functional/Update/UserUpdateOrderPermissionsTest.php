<?php

namespace Drupal\Tests\user\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests user permissions sort upgrade path.
 *
 * @group Update
 * @group legacy
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
    $authenticated = \Drupal::config('user.role.authenticated');
    $permissions = $authenticated->get('permissions');
    sort($permissions);
    $this->assertNotSame($permissions, $authenticated->get('permissions'));

    $this->runUpdates();
    $authenticated = \Drupal::config('user.role.authenticated');
    $this->assertSame($permissions, $authenticated->get('permissions'));
  }

}
