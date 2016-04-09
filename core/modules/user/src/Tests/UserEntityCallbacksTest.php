<?php

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

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
  public static $modules = array('user', 'user_hooks_test');

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
    $this->anonymous = User::create(['uid' => 0]);
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
    $this->assertEqual($this->anonymous->getDisplayName(), $name, 'The variable anonymous should be used for display name of uid 0');
    $this->assertEqual($this->anonymous->getUserName(), '', 'The raw anonymous user name should be empty string');

    // Set to test the altered username.
    \Drupal::state()->set('user_hooks_test_user_format_name_alter', TRUE);

    $this->assertEqual($this->account->getDisplayName(), '<em>' . $this->account->id() . '</em>', 'The user display name should be altered.');
    $this->assertEqual($this->account->getUsername(), $this->account->name->value, 'The user name should not be altered.');
  }

}
