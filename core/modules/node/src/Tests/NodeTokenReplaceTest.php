<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTokenReplaceTest.
 */

namespace Drupal\node\Tests;

use Drupal\system\Tests\System\TokenReplaceUnitTestBase;
use Drupal\Component\Utility\String;

/**
 * Generates text using placeholders for dummy content to check node token
 * replacement.
 *
 * @group node
 */
class NodeTokenReplaceTest extends TokenReplaceUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(array('filter'));

    $node_type = entity_create('node_type', array('type' => 'article', 'name' => 'Article'));
    $node_type->save();
    node_add_body_field($node_type);
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  function testNodeTokenReplacement() {
    $url_options = array(
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    );

    // Create a user and a node.
    $account = $this->createUser();
    /* @var $node \Drupal\node\NodeInterface */
    $node = entity_create('node', array(
      'type' => 'article',
      'tnid' => 0,
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => array(array('value' => $this->randomMachineName(32), 'summary' => $this->randomMachineName(16), 'format' => 'plain_text')),
    ));
    $node->save();

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[node:nid]'] = $node->id();
    $tests['[node:vid]'] = $node->getRevisionId();
    $tests['[node:type]'] = 'article';
    $tests['[node:type-name]'] = 'Article';
    $tests['[node:title]'] = String::checkPlain($node->getTitle());
    $tests['[node:body]'] = $node->body->processed;
    $tests['[node:summary]'] = $node->body->summary_processed;
    $tests['[node:langcode]'] = String::checkPlain($node->language()->id);
    $tests['[node:url]'] = $node->url('canonical', $url_options);
    $tests['[node:edit-url]'] = $node->url('edit-form', $url_options);
    $tests['[node:author]'] = String::checkPlain($account->getUsername());
    $tests['[node:author:uid]'] = $node->getOwnerId();
    $tests['[node:author:name]'] = String::checkPlain($account->getUsername());
    $tests['[node:created:since]'] = \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $node->getCreatedTime(), 2, $this->interfaceLanguage->id);
    $tests['[node:changed:since]'] = \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $node->getChangedTime(), 2, $this->interfaceLanguage->id);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('node' => $node), array('langcode' => $this->interfaceLanguage->id));
      $this->assertEqual($output, $expected, format_string('Sanitized node token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[node:title]'] = $node->getTitle();
    $tests['[node:body]'] = $node->body->value;
    $tests['[node:summary]'] = $node->body->summary;
    $tests['[node:langcode]'] = $node->language()->id;
    $tests['[node:author:name]'] = $account->getUsername();

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('node' => $node), array('langcode' => $this->interfaceLanguage->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized node token %token replaced.', array('%token' => $input)));
    }

    // Repeat for a node without a summary.
    $node = entity_create('node', array(
      'type' => 'article',
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => array(array('value' => $this->randomMachineName(32), 'format' => 'plain_text')),
    ));
    $node->save();

    // Generate and test sanitized token - use full body as expected value.
    $tests = array();
    $tests['[node:summary]'] = $node->body->processed;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated for node without a summary.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('node' => $node), array('language' => $this->interfaceLanguage));
      $this->assertEqual($output, $expected, format_string('Sanitized node token %token replaced for node without a summary.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[node:summary]'] = $node->body->value;

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('node' => $node), array('language' => $this->interfaceLanguage, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized node token %token replaced for node without a summary.', array('%token' => $input)));
    }
  }

}
