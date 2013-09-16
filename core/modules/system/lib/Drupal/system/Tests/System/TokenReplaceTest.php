<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\TokenReplaceTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Language\Language;
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
    $token_service = \Drupal::token();

    // Create the initial objects.
    $account = $this->drupalCreateUser();
    $node = $this->drupalCreateNode(array('uid' => $account->id()));
    $node->title = '<blink>Blinking Text</blink>';
    global $user;
    $language_interface = language(Language::TYPE_INTERFACE);

    $source  = '[node:title]';         // Title of the node we passed in
    $source .= '[node:author:name]';   // Node author's name
    $source .= '[node:created:since]'; // Time since the node was created
    $source .= '[current-user:name]';  // Current user's name
    $source .= '[date:short]';         // Short date format of REQUEST_TIME
    $source .= '[user:name]';          // No user passed in, should be untouched
    $source .= '[bogus:token]';        // Non-existent token

    $target  = check_plain($node->getTitle());
    $target .= check_plain($account->getUsername());
    $target .= format_interval(REQUEST_TIME - $node->getCreatedTime(), 2, $language_interface->id);
    $target .= check_plain($user->getUsername());
    $target .= format_date(REQUEST_TIME, 'short', '', NULL, $language_interface->id);

    // Test that the clear parameter cleans out non-existent tokens.
    $result = $token_service->replace($source, array('node' => $node), array('langcode' => $language_interface->id, 'clear' => TRUE));
    $this->assertEqual($target, $result, 'Valid tokens replaced while invalid tokens cleared out.');

    // Test without using the clear parameter (non-existent token untouched).
    $target .= '[user:name]';
    $target .= '[bogus:token]';
    $result = $token_service->replace($source, array('node' => $node), array('langcode' => $language_interface->id));
    $this->assertEqual($target, $result, 'Valid tokens replaced while invalid tokens ignored.');

    // Check that the results of Token::generate are sanitized properly. This
    // does NOT test the cleanliness of every token -- just that the $sanitize
    // flag is being passed properly through the call stack and being handled
    // correctly by a 'known' token, [node:title].
    $raw_tokens = array('title' => '[node:title]');
    $generated = $token_service->generate('node', $raw_tokens, array('node' => $node));
    $this->assertEqual($generated['[node:title]'], check_plain($node->getTitle()), 'Token sanitized.');

    $generated = $token_service->generate('node', $raw_tokens, array('node' => $node), array('sanitize' => FALSE));
    $this->assertEqual($generated['[node:title]'], $node->getTitle(), 'Unsanitized token generated properly.');

    // Test token replacement when the string contains no tokens.
    $this->assertEqual($token_service->replace('No tokens here.'), 'No tokens here.');
  }

  /**
   * Test whether token-replacement works in various contexts.
   */
  function testSystemTokenRecognition() {
    $token_service = \Drupal::token();
    $language_interface = language(Language::TYPE_INTERFACE);

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
      $output = $token_service->replace($input, array(), array('langcode' => $language_interface->id));
      $this->assertTrue($output == $expected, format_string('Token recognized in string %string', array('%string' => $input)));
    }
  }

  /**
   * Tests the generation of all system site information tokens.
   */
  function testSystemSiteTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = language(Language::TYPE_INTERFACE);
    $url_options = array(
      'absolute' => TRUE,
      'language' => $language_interface,
    );

    // Set a few site variables.
    \Drupal::config('system.site')
      ->set('name', '<strong>Drupal<strong>')
      ->set('slogan', '<blink>Slogan</blink>')
      ->save();

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[site:name]'] = check_plain(\Drupal::config('system.site')->get('name'));
    $tests['[site:slogan]'] = filter_xss_admin(\Drupal::config('system.site')->get('slogan'));
    $tests['[site:mail]'] = 'simpletest@example.com';
    $tests['[site:url]'] = url('<front>', $url_options);
    $tests['[site:url-brief]'] = preg_replace(array('!^https?://!', '!/$!'), '', url('<front>', $url_options));
    $tests['[site:login-url]'] = url('user', $url_options);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array(), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized system site information token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[site:name]'] = \Drupal::config('system.site')->get('name');
    $tests['[site:slogan]'] = \Drupal::config('system.site')->get('slogan');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array(), array('langcode' => $language_interface->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized system site information token %token replaced.', array('%token' => $input)));
    }
  }

  /**
   * Tests the generation of all system date tokens.
   */
  function testSystemDateTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = language(Language::TYPE_INTERFACE);

    // Set time to one hour before request.
    $date = REQUEST_TIME - 3600;

    // Generate and test tokens.
    $tests = array();
    $tests['[date:short]'] = format_date($date, 'short', '', NULL, $language_interface->id);
    $tests['[date:medium]'] = format_date($date, 'medium', '', NULL, $language_interface->id);
    $tests['[date:long]'] = format_date($date, 'long', '', NULL, $language_interface->id);
    $tests['[date:custom:m/j/Y]'] = format_date($date, 'custom', 'm/j/Y', NULL, $language_interface->id);
    $tests['[date:since]'] = format_interval((REQUEST_TIME - $date), 2, $language_interface->id);
    $tests['[date:raw]'] = filter_xss($date);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('date' => $date), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Date token %token replaced.', array('%token' => $input)));
    }
  }
}
