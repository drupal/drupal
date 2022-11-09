<?php

namespace Drupal\Tests\user\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests user_update_10000() upgrade path.
 *
 * @group Update
 * @group legacy
 */
class UserUpdateRoleMigrateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that roles have only existing permissions.
   */
  public function testRolePermissions() {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');

    // Edit the authenticated role to have a non-existent permission.
    $authenticated = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'user.role.authenticated')
      ->execute()
      ->fetchField();
    $authenticated = unserialize($authenticated);
    $authenticated['permissions'][] = 'does_not_exist';
    $connection->update('config')
      ->fields([
        'data' => serialize($authenticated),
      ])
      ->condition('collection', '')
      ->condition('name', 'user.role.authenticated')
      ->execute();

    $authenticated = Role::load('authenticated');
    $this->assertTrue($authenticated->hasPermission('does_not_exist'), 'Authenticated role has a permission that does not exist');

    $this->runUpdates();

    $this->assertSession()->pageTextContains('The role Authenticated user has had non-existent permissions removed. Check the logs for details.');

    $authenticated = Role::load('authenticated');
    $this->assertFalse($authenticated->hasPermission('does_not_exist'), 'Authenticated role does not have a permission that does not exist');

    $this->drupalLogin($this->createUser(['access site reports']));
    $this->drupalGet('admin/reports/dblog', ['query' => ['type[]' => 'update']]);
    $this->clickLink('The role Authenticated user has had the following non-…');
    $this->assertSession()->pageTextContains('The role Authenticated user has had the following non-existent permission(s) removed: does_not_exist.');
  }

}
