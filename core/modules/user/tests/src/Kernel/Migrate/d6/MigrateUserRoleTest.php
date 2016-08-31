<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\user\Entity\Role;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade user roles to user.role.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateUserRoleTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations(['d6_filter_format', 'd6_user_role']);
  }

  /**
   * Tests user role migration.
   */
  public function testUserRole() {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $id_map = $this->getMigration('d6_user_role')->getIdMap();
    $rid = 'anonymous';
    $anonymous = Role::load($rid);
    $this->assertSame($rid, $anonymous->id());
    $this->assertSame(array('migrate test anonymous permission', 'use text format filtered_html'), $anonymous->getPermissions());
    $this->assertSame(array($rid), $id_map->lookupDestinationId(array(1)));
    $rid = 'authenticated';
    $authenticated = Role::load($rid);
    $this->assertSame($rid, $authenticated->id());
    $this->assertSame(array('migrate test authenticated permission', 'use text format filtered_html'), $authenticated->getPermissions());
    $this->assertSame(array($rid), $id_map->lookupDestinationId(array(2)));
    $rid = 'migrate_test_role_1';
    $migrate_test_role_1 = Role::load($rid);
    $this->assertSame($rid, $migrate_test_role_1->id());
    $this->assertSame(array('migrate test role 1 test permission', 'use text format full_html', 'use text format php_code'), $migrate_test_role_1->getPermissions());
    $this->assertSame(array($rid), $id_map->lookupDestinationId(array(3)));
    $rid = 'migrate_test_role_2';
    $migrate_test_role_2 = Role::load($rid);
    $this->assertSame(array(
      'migrate test role 2 test permission',
      'use PHP for settings',
      'administer contact forms',
      'skip comment approval',
      'edit own blog content',
      'edit any blog content',
      'delete own blog content',
      'delete any blog content',
      'create forum content',
      'delete any forum content',
      'delete own forum content',
      'edit any forum content',
      'edit own forum content',
      'administer nodes',
      'access content overview',
      'use text format php_code',
      ), $migrate_test_role_2->getPermissions());
    $this->assertSame($rid, $migrate_test_role_2->id());
    $this->assertSame(array($rid), $id_map->lookupDestinationId(array(4)));
    $rid = 'migrate_test_role_3_that_is_long';
    $migrate_test_role_3 = Role::load($rid);
    $this->assertSame($rid, $migrate_test_role_3->id());
    $this->assertSame(array($rid), $id_map->lookupDestinationId(array(5)));
  }

}
