<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserEntityCallbacksTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user entity callbacks.
 */
class UserEntityCallbacksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user');

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  public static function getInfo() {
    return array(
      'name' => 'User entity callback tests',
      'description' => 'Tests specific parts of the user entity like the URI callback and the label callback.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser();
    $this->anonymous = entity_create('user', array('uid' => 0));
  }

  /**
   * Test label callback.
   */
  function testLabelCallback() {
    $this->assertEqual($this->account->label(), $this->account->getUsername(), 'The username should be used as label');

    // Setup a random anonymous name to be sure the name is used.
    $name = $this->randomName();
    \Drupal::config('user.settings')->set('anonymous', $name)->save();
    $this->assertEqual($this->anonymous->label(), $name, 'The variable anonymous should be used for name of uid 0');
  }

  /**
   * Test URI callback.
   */
  function testUriCallback() {
    $this->assertEqual('user/' . $this->account->id(), $this->account->getSystemPath(), 'Correct user URI.');
  }
}
