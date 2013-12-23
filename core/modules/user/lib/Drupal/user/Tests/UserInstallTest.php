<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserInstallTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests user_install().
 */
class UserInstallTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'User install tests',
      'description' => 'Tests user_install().',
      'group' => 'User'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users'));
  }


  /**
   * Test that the initial users have correct values.
   */
  public function testUserInstall() {
    user_install();
    $anon = db_query('SELECT * FROM {users} WHERE uid = 0')->fetchObject();
    $admin = db_query('SELECT * FROM {users} WHERE uid = 1')->fetchObject();
    $this->assertFalse(empty($anon->uuid), 'Anon user has a UUID');
    $this->assertFalse(empty($admin->uuid), 'Admin user has a UUID');

    $this->assertEqual($anon->langcode, language_default()->id, 'Anon user language is the default.');
    $this->assertEqual($admin->langcode, language_default()->id, 'Admin user language is the default.');

    $this->assertEqual($admin->status, 1, 'Admin user is active.');
    $this->assertEqual($anon->status, 0, 'Anon user is blocked.');
  }

}
