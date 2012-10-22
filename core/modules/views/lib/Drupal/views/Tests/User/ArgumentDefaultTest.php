<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\ArgumentDefaultTest.
 */

namespace Drupal\views\Tests\User;

/**
 * Tests views user argument default plugin.
 */
class ArgumentDefaultTest extends UserTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User: Argument default',
      'description' => 'Tests user argument default plugin.',
      'group' => 'Views Modules',
    );
  }

  public function test_plugin_argument_default_current_user() {
    // Create a user to test.
    $account = $this->drupalCreateUser();

    // Switch the user, we have to check the global user too, because drupalLogin is only for the simpletest browser.
    $this->drupalLogin($account);
    global $user;
    $admin = $user;
    drupal_save_session(FALSE);
    $user = $account;

    $this->view->preExecute();
    $this->view->initHandlers();

    $this->assertEqual($this->view->argument['null']->get_default_argument(), $account->uid, 'Uid of the current user is used.');
    // Switch back.
    $user = $admin;
    drupal_save_session(TRUE);
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_plugin_argument_default_current_user');
  }

}
