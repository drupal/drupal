<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\system\Kernel\Token\TokenReplaceKernelTestBase;

/**
 * Generates text using placeholders for dummy content to check node token
 * replacement.
 *
 * @group node
 */
class NodeTokenReplaceTest extends TokenReplaceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['filter', 'node']);

    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  public function testNodeTokenReplacement() {
    $url_options = [
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    ];

    // Create a user and a node.
    $account = $this->createUser();
    /* @var $node \Drupal\node\NodeInterface */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => [['value' => 'Regular NODE body for the test.', 'summary' => 'Fancy NODE summary.', 'format' => 'plain_text']],
    ]);
    $node->save();

    // Generate and test tokens.
    $tests = [];
    $tests['[node:nid]'] = $node->id();
    $tests['[node:vid]'] = $node->getRevisionId();
    $tests['[node:type]'] = 'article';
    $tests['[node:type-name]'] = 'Article';
    $tests['[node:title]'] = Html::escape($node->getTitle());
    $tests['[node:body]'] = $node->body->processed;
    $tests['[node:summary]'] = $node->body->summary_processed;
    $tests['[node:langcode]'] = $node->language()->getId();
    $tests['[node:url]'] = $node->toUrl('canonical', $url_options)->toString();
    $tests['[node:edit-url]'] = $node->toUrl('edit-form', $url_options)->toString();
    $tests['[node:author]'] = $account->getAccountName();
    $tests['[node:author:uid]'] = $node->getOwnerId();
    $tests['[node:author:name]'] = $account->getAccountName();
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');
    $tests['[node:created:since]'] = $date_formatter->formatTimeDiffSince($node->getCreatedTime(), ['langcode' => $this->interfaceLanguage->getId()]);
    $tests['[node:changed:since]'] = $date_formatter->formatTimeDiffSince($node->getChangedTime(), ['langcode' => $this->interfaceLanguage->getId()]);

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
      $output = $this->tokenService->replace($input, ['node' => $node], ['langcode' => $this->interfaceLanguage->getId()], $bubbleable_metadata);
      $this->assertEqual($output, $expected, format_string('Node token %token replaced.', ['%token' => $input]));
      $this->assertEqual($bubbleable_metadata, $metadata_tests[$input]);
    }

    // Repeat for a node without a summary.
    $node = Node::create([
      'type' => 'article',
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => [['value' => 'A string that looks random like TR5c2I', 'format' => 'plain_text']],
    ]);
    $node->save();

    // Generate and test token - use full body as expected value.
    $tests = [];
    $tests['[node:summary]'] = $node->body->processed;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated for node without a summary.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, ['node' => $node], ['language' => $this->interfaceLanguage]);
      $this->assertEqual($output, $expected, new FormattableMarkup('Node token %token replaced for node without a summary.', ['%token' => $input]));
    }
  }

}
