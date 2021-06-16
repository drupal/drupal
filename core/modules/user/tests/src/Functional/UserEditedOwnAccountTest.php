<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests user edited own account can still log in.
 *
 * @group user
 */
class UserEditedOwnAccountTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testUserEditedOwnAccount() {
    // Change account setting 'Who can register accounts?' to Administrators
    // only.
    $this->config('user.settings')->set('register', UserInterface::REGISTER_ADMINISTRATORS_ONLY)->save();

    // Create a new user account and log in.
    $account = $this->drupalCreateUser(['change own username']);
    $this->drupalLogin($account);

    // Change own username.
    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Log out.
    $this->drupalLogout();

    // Set the new name on the user account and attempt to log back in.
    $account->name = $edit['name'];
    $this->drupalLogin($account);
  }

}
