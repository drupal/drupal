<?php

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\User;

/**
 * Ensure that password reset methods work as expected.
 *
 * @group user
 */
class UserPasswordResetTest extends WebDriverTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * The profile to install as a basis for testing.
   *
   * This test uses the standard profile to test the password reset in
   * combination with an ajax request provided by the user picture configuration
   * in the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * The user object to test password resetting.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user.
    $account = $this->drupalCreateUser();

    // Activate user by logging in.
    $this->drupalLogin($account);

    $this->account = User::load($account->id());
    $this->account->pass_raw = $account->pass_raw;
    $this->drupalLogout();

    // Set the last login time that is used to generate the one-time link so
    // that it is definitely over a second ago.
    $account->login = REQUEST_TIME - mt_rand(10, 100000);
    Database::getConnection()->update('users_field_data')
      ->fields(['login' => $account->getLastLoginTime()])
      ->condition('uid', $account->id())
      ->execute();
  }

  /**
   * Tests password reset functionality with an AJAX form.
   *
   * Make sure the ajax request from uploading a user picture does not
   * invalidate the reset token.
   */
  public function testUserPasswordResetWithAdditionalAjaxForm() {
    $this->drupalGet(Url::fromRoute('user.reset.form', ['uid' => $this->account->id()]));

    // Try to reset the password for an invalid account.
    $this->drupalGet('user/password');

    // Reset the password by username via the password reset page.
    $edit['name'] = $this->account->getAccountName();
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);

    // Login
    $this->drupalPostForm(NULL, NULL, t('Log in'));

    // Generate file.
    $image_file = current($this->drupalGetTestFiles('image'));
    $image_path = \Drupal::service('file_system')->realpath($image_file->uri);

    // Upload file.
    $this->getSession()->getPage()->attachFileToField('Picture', $image_path);
    $this->assertSession()->waitForButton('Remove');

    // Change the forgotten password.
    $password = user_password();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Verify that the password reset session has been destroyed.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Password needed to make profile changes.
    $this->assertSession()->pageTextContains("Your current password is missing or incorrect; it's required to change the Password.");
  }

  /**
   * Retrieves password reset email and extracts the login link.
   */
  public function getResetURL() {
    // Assume the most recent email.
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
    preg_match('#.+user/reset/.+#', $email['body'], $urls);

    return $urls[0];
  }

}
