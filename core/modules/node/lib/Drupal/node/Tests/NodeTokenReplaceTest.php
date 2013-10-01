<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTokenReplaceTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\Language;

/**
 * Test node token replacement in strings.
 */
class NodeTokenReplaceTest extends NodeTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Node token replacement',
      'description' => 'Generates text using placeholders for dummy content to check node token replacement.',
      'group' => 'Node',
    );
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  function testNodeTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = language(Language::TYPE_INTERFACE);
    $url_options = array(
      'absolute' => TRUE,
      'language' => $language_interface,
    );

    // Create a user and a node.
    $account = $this->drupalCreateUser();
    $settings = array(
      'type' => 'article',
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => array(array('value' => $this->randomName(32), 'summary' => $this->randomName(16))),
    );
    $node = $this->drupalCreateNode($settings);

    // Load node so that the body and summary fields are structured properly.
    $node = node_load($node->id());
    $instance = field_info_instance('node', 'body', $node->getType());

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[node:nid]'] = $node->id();
    $tests['[node:vid]'] = $node->getRevisionId();
    $tests['[node:type]'] = 'article';
    $tests['[node:type-name]'] = 'Article';
    $tests['[node:title]'] = check_plain($node->getTitle());
    $tests['[node:body]'] = $node->body->processed;
    $tests['[node:summary]'] = $node->body->summary_processed;
    $tests['[node:langcode]'] = check_plain($node->language()->id);
    $tests['[node:url]'] = url('node/' . $node->id(), $url_options);
    $tests['[node:edit-url]'] = url('node/' . $node->id() . '/edit', $url_options);
    $tests['[node:author]'] = check_plain(user_format_name($account));
    $tests['[node:author:uid]'] = $node->getAuthorId();
    $tests['[node:author:name]'] = check_plain(user_format_name($account));
    $tests['[node:created:since]'] = format_interval(REQUEST_TIME - $node->getCreatedTime(), 2, $language_interface->id);
    $tests['[node:changed:since]'] = format_interval(REQUEST_TIME - $node->getChangedTime(), 2, $language_interface->id);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('node' => $node), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized node token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[node:title]'] = $node->getTitle();
    $tests['[node:title]'] = $node->label();
    $tests['[node:body]'] = $node->body->value;
    $tests['[node:summary]'] = $node->body->summary;
    $tests['[node:langcode]'] = $node->language()->id;
    $tests['[node:author:name]'] = user_format_name($account);

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('node' => $node), array('langcode' => $language_interface->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized node token %token replaced.', array('%token' => $input)));
    }

    // Repeat for a node without a summary.
    $settings['body'] = array(array('value' => $this->randomName(32), 'summary' => ''));
    $node = $this->drupalCreateNode($settings);

    // Load node (without summary) so that the body and summary fields are
    // structured properly.
    $node = node_load($node->id());
    $instance = field_info_instance('node', 'body', $node->getType());

    // Generate and test sanitized token - use full body as expected value.
    $tests = array();
    $tests['[node:summary]'] = $node->body->processed;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated for node without a summary.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('node' => $node), array('language' => $language_interface));
      $this->assertEqual($output, $expected, format_string('Sanitized node token %token replaced for node without a summary.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[node:summary]'] = $node->body->value;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('node' => $node), array('language' => $language_interface, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized node token %token replaced for node without a summary.', array('%token' => $input)));
    }
  }
}
