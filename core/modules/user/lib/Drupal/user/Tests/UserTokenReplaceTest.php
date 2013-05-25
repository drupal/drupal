<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserTokenReplaceTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;

/**
 * Test user token replacement in strings.
 */
class UserTokenReplaceTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  public static function getInfo() {
    return array(
      'name' => 'User token replacement',
      'description' => 'Generates text using placeholders for dummy content to check user token replacement.',
      'group' => 'User',
    );
  }

  public function setUp() {
    parent::setUp();
    $language = new Language(array(
      'langcode' => 'de',
    ));
    language_save($language);
  }

  /**
   * Creates a user, then tests the tokens generated from it.
   */
  function testUserTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = language(Language::TYPE_INTERFACE);
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

    $account = user_load($user1->uid);
    $global_account = user_load($GLOBALS['user']->uid);

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[user:uid]'] = $account->uid;
    $tests['[user:name]'] = check_plain(user_format_name($account));
    $tests['[user:mail]'] = check_plain($account->mail);
    $tests['[user:url]'] = url("user/$account->uid", $url_options);
    $tests['[user:edit-url]'] = url("user/$account->uid/edit", $url_options);
    $tests['[user:last-login]'] = format_date($account->login, 'medium', '', NULL, $language_interface->langcode);
    $tests['[user:last-login:short]'] = format_date($account->login, 'short', '', NULL, $language_interface->langcode);
    $tests['[user:created]'] = format_date($account->created, 'medium', '', NULL, $language_interface->langcode);
    $tests['[user:created:short]'] = format_date($account->created, 'short', '', NULL, $language_interface->langcode);
    $tests['[current-user:name]'] = check_plain(user_format_name($global_account));

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('langcode' => $language_interface->langcode));
      $this->assertEqual($output, $expected, format_string('Sanitized user token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[user:name]'] = user_format_name($account);
    $tests['[user:mail]'] = $account->mail;
    $tests['[current-user:name]'] = user_format_name($global_account);

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('langcode' => $language_interface->langcode, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized user token %token replaced.', array('%token' => $input)));
    }

    // Generate login and cancel link.
    $tests = array();
    $tests['[user:one-time-login-url]'] = user_pass_reset_url($account);
    $tests['[user:cancel-url]'] = user_cancel_url($account);

    // Generate tokens with interface language.
    $link = url('user', array('absolute' => TRUE));
    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('langcode' => $language_interface->langcode, 'callback' => 'user_mail_tokens', 'sanitize' => FALSE, 'clear' => TRUE));
      $this->assertTrue(strpos($output, $link) === 0, 'Generated URL is in interface language.');
    }

    // Generate tokens with the user's preferred language.
    $account->preferred_langcode = 'de';
    $account->save();
    $link = url('user', array('language' => language_load($account->preferred_langcode), 'absolute' => TRUE));
    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('user' => $account), array('callback' => 'user_mail_tokens', 'sanitize' => FALSE, 'clear' => TRUE));
      $this->assertTrue(strpos($output, $link) === 0, "Generated URL is in the user's preferred language.");
    }

    // Generate tokens with one specific language.
    $link = url('user', array('language' => language_load('de'), 'absolute' => TRUE));
    foreach ($tests as $input => $expected) {
      foreach (array($user1, $user2) as $account) {
        $output = $token_service->replace($input, array('user' => $account), array('langcode' => 'de', 'callback' => 'user_mail_tokens', 'sanitize' => FALSE, 'clear' => TRUE));
        $this->assertTrue(strpos($output, $link) === 0, "Generated URL in in the requested language.");
      }
    }
  }
}
