<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\ArgumentDefaultTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests views user argument default plugin.
 *
 * @group user
 */
class ArgumentDefaultTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_plugin_argument_default_current_user');

  public function test_plugin_argument_default_current_user() {
    // Create a user to test.
    $account = $this->drupalCreateUser();

    // Switch the user.
    \Drupal::service('account_switcher')->switchTo($account);

    $view = Views::getView('test_plugin_argument_default_current_user');
    $view->initHandlers();

    $this->assertEqual($view->argument['null']->getDefaultArgument(), $account->id(), 'Uid of the current user is used.');
    // Switch back.
    \Drupal::service('account_switcher')->switchBack();
  }

}
