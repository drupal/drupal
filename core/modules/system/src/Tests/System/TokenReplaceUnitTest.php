<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\TokenReplaceUnitTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;

/**
 * Generates text using placeholders for dummy content to check token
 * replacement.
 *
 * @group system
 */
class TokenReplaceUnitTest extends TokenReplaceUnitTestBase {

  /**
   * Test whether token-replacement works in various contexts.
   */
  public function testSystemTokenRecognition() {
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
      $output = $this->tokenService->replace($input, array(), array('langcode' => $this->interfaceLanguage->id));
      $this->assertTrue($output == $expected, format_string('Token recognized in string %string', array('%string' => $input)));
    }

    // Test token replacement when the string contains no tokens.
    $this->assertEqual($this->tokenService->replace('No tokens here.'), 'No tokens here.');
  }

  /**
   * Tests the clear parameter.
   */
  public function testClear() {
    // Valid token.
    $source = '[site:name]';
    // No user passed in, should be untouched.
    $source .= '[user:name]';
    // Non-existing token.
    $source .= '[bogus:token]';

    // Replace with with the clear parameter, only the valid token should remain.
    $target = String::checkPlain(\Drupal::config('system.site')->get('name'));
    $result = $this->tokenService->replace($source, array(), array('langcode' => $this->interfaceLanguage->id, 'clear' => TRUE));
    $this->assertEqual($target, $result, 'Valid tokens replaced while invalid tokens ignored.');

    $target .= '[user:name]';
    $target .= '[bogus:token]';
    $result = $this->tokenService->replace($source, array(), array('langcode' => $this->interfaceLanguage->id));
    $this->assertEqual($target, $result, 'Valid tokens replaced while invalid tokens ignored.');
  }

  /**
   * Tests the generation of all system site information tokens.
   */
  public function testSystemSiteTokenReplacement() {
    // The use of the url() function requires the url_alias table to exist.
    $this->installSchema('system', 'url_alias');
    $url_options = array(
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    );

    $slogan = '<blink>Slogan</blink>';
    $safe_slogan = Xss::filterAdmin($slogan);

    // Set a few site variables.
    $config = $this->container->get('config.factory')->get('system.site');
    $config
      ->set('name', '<strong>Drupal<strong>')
      ->set('slogan', $slogan)
      ->set('mail', 'simpletest@example.com')
      ->save();


    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[site:name]'] = String::checkPlain($config->get('name'));
    $tests['[site:slogan]'] = $safe_slogan;
    $tests['[site:mail]'] = $config->get('mail');
    $tests['[site:url]'] = url('<front>', $url_options);
    $tests['[site:url-brief]'] = preg_replace(array('!^https?://!', '!/$!'), '', url('<front>', $url_options));
    $tests['[site:login-url]'] = url('user', $url_options);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array(), array('langcode' => $this->interfaceLanguage->id));
      $this->assertEqual($output, $expected, format_string('Sanitized system site information token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[site:name]'] = $config->get('name');
    $tests['[site:slogan]'] = $config->get('slogan');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array(), array('langcode' => $this->interfaceLanguage->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized system site information token %token replaced.', array('%token' => $input)));
    }

    // Check that the results of Token::generate are sanitized properly. This
    // does NOT test the cleanliness of every token -- just that the $sanitize
    // flag is being passed properly through the call stack and being handled
    // correctly by a 'known' token, [site:slogan].
    $raw_tokens = array('slogan' => '[site:slogan]');
    $generated = $this->tokenService->generate('site', $raw_tokens);
    $this->assertEqual($generated['[site:slogan]'], $safe_slogan, 'Token sanitized.');

    $generated = $this->tokenService->generate('site', $raw_tokens, array(), array('sanitize' => FALSE));
    $this->assertEqual($generated['[site:slogan]'], $slogan, 'Unsanitized token generated properly.');
  }

  /**
   * Tests the generation of all system date tokens.
   */
  public function testSystemDateTokenReplacement() {
    // Set time to one hour before request.
    $date = REQUEST_TIME - 3600;

    // Generate and test tokens.
    $tests = array();
    $date_formatter = \Drupal::service('date.formatter');
    $tests['[date:short]'] = $date_formatter->format($date, 'short', '', NULL, $this->interfaceLanguage->id);
    $tests['[date:medium]'] = $date_formatter->format($date, 'medium', '', NULL, $this->interfaceLanguage->id);
    $tests['[date:long]'] = $date_formatter->format($date, 'long', '', NULL, $this->interfaceLanguage->id);
    $tests['[date:custom:m/j/Y]'] = $date_formatter->format($date, 'custom', 'm/j/Y', NULL, $this->interfaceLanguage->id);
    $tests['[date:since]'] = $date_formatter->formatInterval(REQUEST_TIME - $date, 2, $this->interfaceLanguage->id);
    $tests['[date:raw]'] = Xss::filter($date);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('date' => $date), array('langcode' => $this->interfaceLanguage->id));
      $this->assertEqual($output, $expected, format_string('Date token %token replaced.', array('%token' => $input)));
    }
  }
}
