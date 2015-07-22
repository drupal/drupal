<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeTokenReplaceTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\system\Tests\System\TokenReplaceUnitTestBase;
use Drupal\Component\Utility\SafeMarkup;

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
    $this->installConfig(array('filter', 'node'));

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
    $tests['[node:title]'] = SafeMarkup::checkPlain($node->getTitle());
    $tests['[node:body]'] = $node->body->processed;
    $tests['[node:summary]'] = $node->body->summary_processed;
    $tests['[node:langcode]'] = SafeMarkup::checkPlain($node->language()->getId());
    $tests['[node:url]'] = $node->url('canonical', $url_options);
    $tests['[node:edit-url]'] = $node->url('edit-form', $url_options);
    $tests['[node:author]'] = SafeMarkup::checkPlain($account->getUsername());
    $tests['[node:author:uid]'] = $node->getOwnerId();
    $tests['[node:author:name]'] = SafeMarkup::checkPlain($account->getUsername());
    $tests['[node:created:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($node->getCreatedTime(), array('langcode' => $this->interfaceLanguage->getId()));
    $tests['[node:changed:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($node->getChangedTime(), array('langcode' => $this->interfaceLanguage->getId()));

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($node);

    $metadata_tests = [];
    $metadata_tests['[node:nid]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:vid]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:type]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:type-name]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:title]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:body]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:summary]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:langcode]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:edit-url]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:author]'] = $bubbleable_metadata->addCacheTags(['user:1']);
    $metadata_tests['[node:author:uid]'] = $bubbleable_metadata;
    $metadata_tests['[node:author:name]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:created:since]'] = $bubbleable_metadata->setCacheMaxAge(0);
    $metadata_tests['[node:changed:since]'] = $bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($input, array('node' => $node), array('langcode' => $this->interfaceLanguage->getId()), $bubbleable_metadata);
      $this->assertEqual($output, $expected, format_string('Sanitized node token %token replaced.', array('%token' => $input)));
      $this->assertEqual($bubbleable_metadata, $metadata_tests[$input]);
    }

    // Generate and test unsanitized tokens.
    $tests['[node:title]'] = $node->getTitle();
    $tests['[node:body]'] = $node->body->value;
    $tests['[node:summary]'] = $node->body->summary;
    $tests['[node:langcode]'] = $node->language()->getId();
    $tests['[node:author:name]'] = $account->getUsername();

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('node' => $node), array('langcode' => $this->interfaceLanguage->getId(), 'sanitize' => FALSE));
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
