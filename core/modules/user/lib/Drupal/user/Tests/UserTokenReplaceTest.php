<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserTokenReplaceTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user token replacement in strings.
 */
class UserTokenReplaceTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User token replacement',
      'description' => 'Generates text using placeholders for dummy content to check user token replacement.',
      'group' => 'User',
    );
  }

  /**
   * Creates a user, then tests the tokens generated from it.
   */
  function testUserTokenReplacement() {
    $language_interface = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);
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
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), t('No empty tokens generated.'));

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('user' => $account), array('language' => $language_interface));
      $this->assertEqual($output, $expected, t('Sanitized user token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[user:name]'] = user_format_name($account);
    $tests['[user:mail]'] = $account->mail;
    $tests['[current-user:name]'] = user_format_name($global_account);

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('user' => $account), array('language' => $language_interface, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, t('Unsanitized user token %token replaced.', array('%token' => $input)));
    }
  }
}
