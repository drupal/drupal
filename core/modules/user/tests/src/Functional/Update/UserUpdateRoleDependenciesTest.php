<?php

namespace Drupal\Tests\user\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests user_post_update_update_roles() upgrade path.
 *
 * @group Update
 * @group legacy
 */
class UserUpdateRoleDependenciesTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that roles have dependencies and only existing permissions.
   */
  public function testRolePermissions() {
    // Edit the role to have a non-existent permission.
    $raw_config = $this->config('user.role.authenticated');
    $permissions = $raw_config->get('permissions');
    $permissions[] = 'does_not_exist';
    $raw_config
      ->set('permissions', $permissions)
      ->save();

    $authenticated = Role::load('authenticated');
    $this->assertTrue($authenticated->hasPermission('does_not_exist'), 'Authenticated role has a permission that does not exist');
    $this->assertEquals([], $authenticated->getDependencies());

    $this->runUpdates();
    $this->assertSession()->pageTextContains('The roles Anonymous user, Authenticated user have had non-existent permissions removed. Check the logs for details.');
    $authenticated = Role::load('authenticated');
    $this->assertFalse($authenticated->hasPermission('does_not_exist'), 'Authenticated role does not have a permission that does not exist');
    $this->assertEquals(['config' => ['filter.format.basic_html'], 'module' => ['comment', 'contact', 'filter', 'shortcut', 'system']], $authenticated->getDependencies());

    $this->drupalLogin($this->createUser(['access site reports']));
    $this->drupalGet('admin/reports/dblog', ['query' => ['type[]' => 'update']]);
    $this->clickLink('The role Authenticated user has had the following non-…');
    $this->assertSession()->pageTextContains('The role Authenticated user has had the following non-existent permission(s) removed: does_not_exist, use text format plain_text.');
    $this->getSession()->back();
    $this->clickLink('The role Anonymous user has had the following non-…');
    $this->assertSession()->pageTextContains('The role Anonymous user has had the following non-existent permission(s) removed: use text format plain_text.');
  }

}
