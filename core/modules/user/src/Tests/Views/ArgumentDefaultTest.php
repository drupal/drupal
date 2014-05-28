<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\ArgumentDefaultTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests views user argument default plugin.
 */
class ArgumentDefaultTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_plugin_argument_default_current_user');

  public static function getInfo() {
    return array(
      'name' => 'User: Argument default',
      'description' => 'Tests user argument default plugin.',
      'group' => 'Views module integration',
    );
  }

  public function test_plugin_argument_default_current_user() {
    // Create a user to test.
    $account = $this->drupalCreateUser();

    // Switch the user, we have to check the global user too, because drupalLogin is only for the simpletest browser.
    $this->drupalLogin($account);
    global $user;
    $admin = $user;
    $session_manager = \Drupal::service('session_manager')->disable();
    $user = $account;

    $view = Views::getView('test_plugin_argument_default_current_user');
    $view->initHandlers();

    $this->assertEqual($view->argument['null']->getDefaultArgument(), $account->id(), 'Uid of the current user is used.');
    // Switch back.
    $user = $admin;
    $session_manager->enable();
  }

}
