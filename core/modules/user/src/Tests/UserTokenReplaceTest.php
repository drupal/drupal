<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserTokenReplaceTest.
 */

namespace Drupal\user\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Generates text using placeholders for dummy content to check user token
 * replacement.
 *
 * @group user
 */
class UserTokenReplaceTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  protected function setUp() {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Creates a user, then tests the tokens generated from it.
   */
  function testUserTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $url_options = array(
      'absolute' => TRUE,
      'language' => $language_interface,
    );

    // Create two users and log them in one after another.
    $user1 = $this->drupalCreateUser(array());
    $user2 = $this->drupalCreateUser(array());
    $this->drupalLogin($user1);
    $this->drupalLogout();
    $this->drupalLogin($user2);

    $account = User::load($user1->id());
    $global_account = User::load(\Drupal::currentUser()->id());

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[user:uid]'] = $account->id();
    $tests['[user:name]'] = SafeMarkup::checkPlain(user_format_name($account));
    $tests['[user:mail]'] = SafeMarkup::checkPlain($account->getEmail());
    $tests['[user:url]'] = $account->url('canonical', $url_options);
    $tests['[user:edit-url]'] = $account->url('edit-form', $url_options);
    $tests['[user:last-login]'] = format_date($account->getLastLoginTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[user:last-login:short]'] = format_date($account->getLastLoginTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[user:created]'] = format_date($account->getCreatedTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[user:created:short]'] = format_date($account->getCreatedTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[current-user:name]'] = SafeMarkup::checkPlain(user_format_name($global_account));

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('langcode' => $language_interface->getId()));
      $this->assertEqual($output, $expected, format_string('Sanitized user token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[user:name]'] = user_format_name($account);
    $tests['[user:mail]'] = $account->getEmail();
    $tests['[current-user:name]'] = user_format_name($global_account);

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('langcode' => $language_interface->getId(), 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized user token %token replaced.', array('%token' => $input)));
    }

    // Generate login and cancel link.
    $tests = array();
    $tests['[user:one-time-login-url]'] = user_pass_reset_url($account);
    $tests['[user:cancel-url]'] = user_cancel_url($account);

    // Generate tokens with interface language.
    $link = \Drupal::url('user.page', [], array('absolute' => TRUE));
    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('langcode' => $language_interface->getId(), 'callback' => 'user_mail_tokens', 'sanitize' => FALSE, 'clear' => TRUE));
      $this->assertTrue(strpos($output, $link) === 0, 'Generated URL is in interface language.');
    }

    // Generate tokens with the user's preferred language.
    $account->preferred_langcode = 'de';
    $account->save();
    $link = \Drupal::url('user.page', [], array('language' => \Drupal::languageManager()->getLanguage($account->getPreferredLangcode()), 'absolute' => TRUE));
    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('callback' => 'user_mail_tokens', 'sanitize' => FALSE, 'clear' => TRUE));
      $this->assertTrue(strpos($output, $link) === 0, "Generated URL is in the user's preferred language.");
    }

    // Generate tokens with one specific language.
    $link = \Drupal::url('user.page', [], array('language' => \Drupal::languageManager()->getLanguage('de'), 'absolute' => TRUE));
    foreach ($tests as $input => $expected) {
      foreach (array($user1, $user2) as $account) {
        $output = $token_service->replace($input, array('user' => $account), array('langcode' => 'de', 'callback' => 'user_mail_tokens', 'sanitize' => FALSE, 'clear' => TRUE));
        $this->assertTrue(strpos($output, $link) === 0, "Generated URL in in the requested language.");
      }
    }
  }
}
