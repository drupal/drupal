<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Controller\MailChangeController;
use Drupal\user\Entity\User;

/**
 * Ensures that email change works as expected.
 *
 * @group user
 */
class UserMailChangeTest extends BrowserTestBase {

  use AssertMailTrait;

  /**
   * The user object to test password resetting for.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The date/time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a user.
    $this->account = $this->drupalCreateUser();
    $this->time = $this->container->get('datetime.time');
  }

  /**
   * Tests email change functionality.
   */
  public function testMailChange() {
    $this->drupalLogin($this->account);

    // Ensure a time between the user last login and the time the account edit
    // is posted. A human cannot login, edit the account and post the changes
    // within the same second. But tests occasionally are running all steps in
    // the same timestamp, so that the mail change URL timestamp equals the user
    // last login timestamp. Later, in this test, when the user tries to reuse
    // the expired link, the test is still within the timestamp when the user
    // has logged in and a time difference cannot be experienced a because the
    // user last login time has seconds as granularity.
    sleep(1);

    // Change the user email address.
    $new_mail = $this->getRandomEmailAddress();
    $edit = [
      'mail' => $new_mail,
      'current_pass' => $this->account->pass_raw,
    ];
    $this->drupalPostForm($this->account->toUrl('edit-form'), $edit, 'Save');

    // Check that the validation status message has been displayed.
    $this->assertSession()->pageTextContains('Your updated email address needs to be validated. Further instructions have been sent to your new email address.');

    $user_mail = $this->config('user.mail');

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = $this->container->get('token');

    // Check that a notification mail has been sent.
    $this->assertMail('to', $this->account->getEmail());
    $subject = $token_service->replace($user_mail->get('mail_change_notification.subject'), ['user' => $this->account]);
    $this->assertMail('subject', $subject);

    // Check that a verification mail has been sent.
    $this->assertMailString('to', $new_mail, 2);
    $subject = $token_service->replace($user_mail->get('mail_change_verification.subject'), ['user' => $this->account]);
    $this->assertMailString('subject', $subject, 2);

    $sent_mail_change_url = $this->extractUrlFromMail('user_mail_change_verification');

    // Check that the email has been successfully updated.
    $this->drupalGet($sent_mail_change_url);
    $this->assertSession()->responseContains(new FormattableMarkup('Your email address has been changed to %mail.', ['%mail' => $new_mail]));

    // Check that the change mail URL is not cached and expires after first use.
    $this->drupalGet($sent_mail_change_url);
    $this->assertNull($this->drupalGetHeader('X-Drupal-Cache'));
    $this->assertSession()->responseContains('You have tried to use an email address change link that has either been used or is no longer valid. Please visit your account and change your email again.');

    // Check that the user mail has been changed.
    self::assertSame(User::load($this->account->id())->getEmail(), $new_mail);
  }

  /**
   * Tests email change functionality for a user without E-mail address.
   *
   * Drupal allows accounts without E-mail when the account is created by an
   * administrator. The other way around, changing an non-empty E-mail to an
   * empty one, is not allowed.
   */
  public  function testMailChangeForUserWithEmptyEmail() {
    // Simulate a user without an E-mail address.
    $this->account->setEmail(NULL)->save();

    $this->drupalLogin($this->account);
    $edit = [
      'mail' => $this->getRandomEmailAddress(),
      'current_pass' => $this->account->pass_raw,
    ];
    $this->drupalPostForm($this->account->toUrl('edit-form'), $edit, 'Save');

    // Check that the validation status message has been displayed.
    $this->assertSession()->pageTextContains('Your updated email address needs to be validated. Further instructions have been sent to your new email address.');

    // Check that only the verification message was sent.
    $this->assertCount(1, $this->getMails());
    $this->assertNotEmpty($this->getMails(['id' => 'user_mail_change_verification']));
  }

  /**
   * Tests email change functionality when E-mail change verification is off.
   */
  public function testMailChangeNoVerification() {
    // Disable mail change verification.
    $this->config('user.settings')
      ->set('notify.mail_change_verification', FALSE)
      ->save();
    $this->drupalLogin($this->account);

    // Change the user email address.
    $new_mail = $this->getRandomEmailAddress();
    $edit = [
      'mail' => $new_mail,
      'current_pass' => $this->account->pass_raw,
    ];
    $this->drupalPostForm($this->account->toUrl('edit-form'), $edit, 'Save');

    // Check that the validation status message has not been displayed.
    $this->assertSession()->pageTextNotContains('Your updated email address needs to be validated. Further instructions have been sent to your new email address.');

    // Check that no E-mail was sent to the old or to the new address.
    $this->assertEmpty($this->getMails());

    // Check that the user's E-mail was changed instantly.
    self::assertSame($new_mail, User::load($this->account->id())->getEmail());
  }

  /**
   * Tests change of email for blocked users.
   */
  public function testBlockedUser() {
    $timestamp = $this->time->getRequestTime() - 1;
    $account_cloned = clone $this->account;
    $account_cloned->block()->save();
    $this->drupalGet(MailChangeController::getUrl($account_cloned, [], $timestamp)->getInternalPath());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests change of email for expired timestamp.
   */
  public function testExpiredTimestamp() {
    $timestamp = $this->time->getRequestTime() - (24 * 60 * 60 + 1);
    $this->drupalGet(MailChangeController::getUrl($this->account, [], $timestamp)->getInternalPath());
    $this->assertSession()->responseContains('You have tried to use an email address change link that has expired. Please visit your account and change your email again.');
  }

  /**
   * Tests change of email when other user is logged in.
   */
  public function testOtherUserLoggedIn() {
    $timestamp = $this->time->getRequestTime() - 1;
    // Create other account and login with it.
    $current_account = $this->drupalCreateUser();
    $this->drupalLogin($current_account);
    // Try to change the email for the first account when the other account is
    // logged in.
    $new_mail = $this->getRandomEmailAddress();
    $this->account->setEmail($new_mail);
    $path = MailChangeController::getUrl($this->account, [], $timestamp)->getInternalPath();
    $this->drupalGet($path);
    $this->assertSession()->responseContains(new FormattableMarkup('You are currently logged in as %user, and are attempting to confirm an email address change for another account. Please <a href=":logout">log out</a> and try using the link again.', ['%user' => $current_account->getAccountName(), ':logout' => Url::fromRoute('user.logout')->toString()]));

    // Retry as anonymous.
    $this->drupalLogout();
    $this->drupalGet($path);
    $this->assertSession()->responseContains(new FormattableMarkup('Your email address has been changed to %mail.', ['%mail' => $new_mail]));
  }

  /**
   * Tests change of email for timestamp in the future.
   */
  public function testFutureTimestamp() {
    $timestamp = $this->time->getRequestTime() + 60 * 60;
    $this->drupalGet(MailChangeController::getUrl($this->account, [], $timestamp)->getInternalPath());
    $this->assertSession()->responseContains('You have tried to use an email address change link that has either been used or is no longer valid. Please visit your account and change your email again.');
  }

  /**
   * Tests change of email with the wrong hash.
   */
  public function testWrongHash() {
    $timestamp = $this->time->getRequestTime() - 1;
    // Generate the hash for other user.
    $other_account = $this->drupalCreateUser();
    $hash = user_pass_rehash($other_account, $timestamp);
    $this->drupalGet(MailChangeController::getUrl($this->account, [], $timestamp, $hash)->getInternalPath());
    $this->assertSession()->responseContains('You have tried to use an email address change link that has either been used or is no longer valid. Please visit your account and change your email again.');
  }

  /**
   * Retrieves the change email and extracts the link.
   *
   * @param string $mail_id
   *   Unique mail ID.
   *
   * @return string
   *   An URL.
   */
  protected function extractUrlFromMail($mail_id) {
    // Assume the most recent email.
    $email = $this->getMails(['id' => $mail_id]);
    $email = end($email);
    preg_match('#.+user\/mail\-change\/.+#', $email['body'], $urls);
    return $urls[0];
  }

  /**
   * Generates a random email address.
   *
   * @return string
   *   A random email address.
   */
  protected function getRandomEmailAddress() {
    return mb_strtolower($this->randomMachineName()) . '@example.com';
  }

}
