<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserEntityCallbacksTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests specific parts of the user entity like the URI callback and the label
 * callback.
 *
 * @group user
 */
class UserEntityCallbacksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user');

  /**
   * An authenticated user to use for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * An anonymous user to use for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymous;

  protected function setUp() {
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
    $name = $this->randomMachineName();
    $this->config('user.settings')->set('anonymous', $name)->save();
    $this->assertEqual($this->anonymous->label(), $name, 'The variable anonymous should be used for name of uid 0');
  }

}
