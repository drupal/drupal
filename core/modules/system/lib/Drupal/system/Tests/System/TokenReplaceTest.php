<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\TokenReplaceTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Test token replacement in strings.
 */
class TokenReplaceTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Token replacement',
      'description' => 'Generates text using placeholders for dummy content to check token replacement.',
      'group' => 'System',
    );
  }

  /**
   * Creates a user and a node, then tests the tokens generated from them.
   */
  function testTokenReplacement() {
    // Create the initial objects.
    $account = $this->drupalCreateUser();
    $node = $this->drupalCreateNode(array('uid' => $account->uid));
    $node->title = '<blink>Blinking Text</blink>';
    global $user, $language_interface;

    $source  = '[node:title]';         // Title of the node we passed in
    $source .= '[node:author:name]';   // Node author's name
    $source .= '[node:created:since]'; // Time since the node was created
    $source .= '[current-user:name]';  // Current user's name
    $source .= '[date:short]';         // Short date format of REQUEST_TIME
    $source .= '[user:name]';          // No user passed in, should be untouched
    $source .= '[bogus:token]';        // Non-existent token

    $target  = check_plain($node->title);
    $target .= check_plain($account->name);
    $target .= format_interval(REQUEST_TIME - $node->created, 2, $language_interface->langcode);
    $target .= check_plain($user->name);
    $target .= format_date(REQUEST_TIME, 'short', '', NULL, $language_interface->langcode);

    // Test that the clear parameter cleans out non-existent tokens.
    $result = token_replace($source, array('node' => $node), array('language' => $language_interface, 'clear' => TRUE));
    $result = $this->assertEqual($target, $result, 'Valid tokens replaced while invalid tokens cleared out.');

    // Test without using the clear parameter (non-existent token untouched).
    $target .= '[user:name]';
    $target .= '[bogus:token]';
    $result = token_replace($source, array('node' => $node), array('language' => $language_interface));
    $this->assertEqual($target, $result, 'Valid tokens replaced while invalid tokens ignored.');

    // Check that the results of token_generate are sanitized properly. This does NOT
    // test the cleanliness of every token -- just that the $sanitize flag is being
    // passed properly through the call stack and being handled correctly by a 'known'
    // token, [node:title].
    $raw_tokens = array('title' => '[node:title]');
    $generated = token_generate('node', $raw_tokens, array('node' => $node));
    $this->assertEqual($generated['[node:title]'], check_plain($node->title), t('Token sanitized.'));

    $generated = token_generate('node', $raw_tokens, array('node' => $node), array('sanitize' => FALSE));
    $this->assertEqual($generated['[node:title]'], $node->title, t('Unsanitized token generated properly.'));

    // Test token replacement when the string contains no tokens.
    $this->assertEqual(token_replace('No tokens here.'), 'No tokens here.');
  }

  /**
   * Test whether token-replacement works in various contexts.
   */
  function testSystemTokenRecognition() {
    global $language_interface;

    // Generate prefixes and suffixes for the token context.
    $tests = array(
      array('prefix' => 'this is the ', 'suffix' => ' site'),
      array('prefix' => 'this is the', 'suffix' => 'site'),
      array('prefix' => '[', 'suffix' => ']'),
      array('prefix' => '', 'suffix' => ']]]'),
      array('prefix' => '[[[', 'suffix' => ''),
      array('prefix' => ':[:', 'suffix' => '--]'),
      array('prefix' => '-[-', 'suffix' => ':]:'),
      array('prefix' => '[:', 'suffix' => ']'),
      array('prefix' => '[site:', 'suffix' => ':name]'),
      array('prefix' => '[site:', 'suffix' => ']'),
    );

    // Check if the token is recognized in each of the contexts.
    foreach ($tests as $test) {
      $input = $test['prefix'] . '[site:name]' . $test['suffix'];
      $expected = $test['prefix'] . 'Drupal' . $test['suffix'];
      $output = token_replace($input, array(), array('language' => $language_interface));
      $this->assertTrue($output == $expected, t('Token recognized in string %string', array('%string' => $input)));
    }
  }

  /**
   * Tests the generation of all system site information tokens.
   */
  function testSystemSiteTokenReplacement() {
    global $language_interface;
    $url_options = array(
      'absolute' => TRUE,
      'language' => $language_interface,
    );

    // Set a few site variables.
    variable_set('site_name', '<strong>Drupal<strong>');
    variable_set('site_slogan', '<blink>Slogan</blink>');

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[site:name]'] = check_plain(variable_get('site_name', 'Drupal'));
    $tests['[site:slogan]'] = check_plain(variable_get('site_slogan', ''));
    $tests['[site:mail]'] = 'simpletest@example.com';
    $tests['[site:url]'] = url('<front>', $url_options);
    $tests['[site:url-brief]'] = preg_replace(array('!^https?://!', '!/$!'), '', url('<front>', $url_options));
    $tests['[site:login-url]'] = url('user', $url_options);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), t('No empty tokens generated.'));

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array(), array('language' => $language_interface));
      $this->assertEqual($output, $expected, t('Sanitized system site information token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[site:name]'] = variable_get('site_name', 'Drupal');
    $tests['[site:slogan]'] = variable_get('site_slogan', '');

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array(), array('language' => $language_interface, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, t('Unsanitized system site information token %token replaced.', array('%token' => $input)));
    }
  }

  /**
   * Tests the generation of all system date tokens.
   */
  function testSystemDateTokenReplacement() {
    global $language_interface;

    // Set time to one hour before request.
    $date = REQUEST_TIME - 3600;

    // Generate and test tokens.
    $tests = array();
    $tests['[date:short]'] = format_date($date, 'short', '', NULL, $language_interface->langcode);
    $tests['[date:medium]'] = format_date($date, 'medium', '', NULL, $language_interface->langcode);
    $tests['[date:long]'] = format_date($date, 'long', '', NULL, $language_interface->langcode);
    $tests['[date:custom:m/j/Y]'] = format_date($date, 'custom', 'm/j/Y', NULL, $language_interface->langcode);
    $tests['[date:since]'] = format_interval((REQUEST_TIME - $date), 2, $language_interface->langcode);
    $tests['[date:raw]'] = filter_xss($date);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), t('No empty tokens generated.'));

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('date' => $date), array('language' => $language_interface));
      $this->assertEqual($output, $expected, t('Date token %token replaced.', array('%token' => $input)));
    }
  }
}
