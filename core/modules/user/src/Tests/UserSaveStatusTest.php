<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserSaveStatusTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests user saving status.
 *
 * @group user
 */
class UserSaveStatusTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field');

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Test SAVED_NEW and SAVED_UPDATED statuses for user entity type.
   */
  function testUserSaveStatus() {
    // Create a new user.
    $values = array(
      'uid' => 1,
      'name' => $this->randomMachineName(),
    );
    $user = User::create($values);

    // Test SAVED_NEW.
    $return = $user->save();
    $this->assertEqual($return, SAVED_NEW, "User was saved with SAVED_NEW status.");

    // Test SAVED_UPDATED.
    $user->name = $this->randomMachineName();
    $return = $user->save();
    $this->assertEqual($return, SAVED_UPDATED, "User was saved with SAVED_UPDATED status.");
  }

}
