<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d6\MigrateUserRoleTest.
 */

namespace Drupal\user\Tests\Migrate\d6;

use Drupal\user\Entity\Role;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade user roles to user.role.*.yml.
 *
 * @group user
 */
class MigrateUserRoleTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('filter', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // We need some sample data so we can use the Migration process plugin.
    $id_mappings = array(
      'd6_filter_format' => array(
        array(array(1), array('filtered_html')),
        array(array(2), array('full_html'))
      ),
    );
    $this->prepareMigrations($id_mappings);
    $this->executeMigration('d6_user_role');
  }

  /**
   * Tests user role migration.
   */
  public function testUserRole() {
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_user_role');
    $rid = 'anonymous';
    $anonymous = Role::load($rid);
    $this->assertIdentical($rid, $anonymous->id());
    $this->assertIdentical(array('migrate test anonymous permission', 'use text format filtered_html'), $anonymous->getPermissions());
    $this->assertIdentical(array($rid), $migration->getIdMap()->lookupDestinationId(array(1)));
    $rid = 'authenticated';
    $authenticated = Role::load($rid);
    $this->assertIdentical($rid, $authenticated->id());
    $this->assertIdentical(array('migrate test authenticated permission', 'use text format filtered_html'), $authenticated->getPermissions());
    $this->assertIdentical(array($rid), $migration->getIdMap()->lookupDestinationId(array(2)));
    $rid = 'migrate_test_role_1';
    $migrate_test_role_1 = Role::load($rid);
    $this->assertIdentical($rid, $migrate_test_role_1->id());
    $this->assertIdentical(array(0 => 'migrate test role 1 test permission', 'use text format full_html'), $migrate_test_role_1->getPermissions());
    $this->assertIdentical(array($rid), $migration->getIdMap()->lookupDestinationId(array(3)));
    $rid = 'migrate_test_role_2';
    $migrate_test_role_2 = Role::load($rid);
    $this->assertIdentical(array(
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
      ), $migrate_test_role_2->getPermissions());
    $this->assertIdentical($rid, $migrate_test_role_2->id());
    $this->assertIdentical(array($rid), $migration->getIdMap()->lookupDestinationId(array(4)));
    $rid = 'migrate_test_role_3_that_is_long';
    $migrate_test_role_3 = Role::load($rid);
    $this->assertIdentical($rid, $migrate_test_role_3->id());
    $this->assertIdentical(array($rid), $migration->getIdMap()->lookupDestinationId(array(5)));
  }

}
