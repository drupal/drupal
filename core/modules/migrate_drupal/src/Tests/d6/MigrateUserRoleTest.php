<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserRoleTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\user\Entity\Role;

/**
 * Upgrade user roles to user.role.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateUserRoleTest extends MigrateDrupalTestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('filter');

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

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_user_role');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6UserRole.php',
      $this->getDumpDirectory() . '/Drupal6FilterFormat.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests user role migration.
   */
  public function testUserRole() {
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_user_role');
    $rid = 'anonymous';
    $anonymous = Role::load($rid);
    $this->assertEqual($anonymous->id(), $rid);
    $this->assertEqual($anonymous->getPermissions(), array('migrate test anonymous permission', 'use text format filtered_html'));
    $this->assertEqual(array($rid), $migration->getIdMap()->lookupDestinationId(array(1)));
    $rid = 'authenticated';
    $authenticated = Role::load($rid);
    $this->assertEqual($authenticated->id(), $rid);
    $this->assertEqual($authenticated->getPermissions(), array('migrate test authenticated permission', 'use text format filtered_html'));
    $this->assertEqual(array($rid), $migration->getIdMap()->lookupDestinationId(array(2)));
    $rid = 'migrate_test_role_1';
    $migrate_test_role_1 = Role::load($rid);
    $this->assertEqual($migrate_test_role_1->id(), $rid);
    $this->assertEqual($migrate_test_role_1->getPermissions(), array(0 => 'migrate test role 1 test permission', 'use text format full_html'));
    $this->assertEqual(array($rid), $migration->getIdMap()->lookupDestinationId(array(3)));
    $rid = 'migrate_test_role_2';
    $migrate_test_role_2 = Role::load($rid);
    $this->assertEqual($migrate_test_role_2->getPermissions(), array(
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
    ));
    $this->assertEqual($migrate_test_role_2->id(), $rid);
    $this->assertEqual(array($rid), $migration->getIdMap()->lookupDestinationId(array(4)));
    $rid = 'migrate_test_role_3_that_is_long';
    $migrate_test_role_3 = Role::load($rid);
    $this->assertEqual($migrate_test_role_3->id(), $rid);
    $this->assertEqual(array($rid), $migration->getIdMap()->lookupDestinationId(array(5)));
  }

}
