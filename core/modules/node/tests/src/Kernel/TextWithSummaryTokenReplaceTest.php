<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\system\Kernel\Token\TokenReplaceKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node token replacement for text_with_summary.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class TextWithSummaryTokenReplaceTest extends TokenReplaceKernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['filter', 'node']);

    // We don't create a default body field as we need a text_with_summary.
    $this->createContentType(['type' => 'article', 'name' => 'Article'], FALSE);
    FieldStorageConfig::create([
      'field_name' => 'body',
      'type' => 'text_with_summary',
      'entity_type' => 'node',
      'cardinality' => 1,
      'persist_with_no_fields' => TRUE,
    ])->save();
    $fieldStorage = FieldStorageConfig::loadByName('node', 'body');
    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'article',
      'label' => 'Body',
      'settings' => [
        'display_summary' => TRUE,
        'allowed_formats' => [],
      ],
    ])->save();
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  public function testNodeTokenReplacement(): void {
    // Create a user and a node.
    $account = $this->createUser();
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => [
        [
          'value' => 'Regular NODE body for the test.',
          'summary' => 'Fancy NODE summary.',
          'format' => 'plain_text',
        ],
      ],
    ]);
    $node->save();

    // Generate and test tokens.
    $tests = [];
    $tests['[node:body]'] = $node->body->processed;
    $tests['[node:summary]'] = $node->body->summary_processed;
    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($node);

    $metadata_tests = [];
    $metadata_tests['[node:body]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:summary]'] = $base_bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertNotContains(0, array_map('strlen', $tests), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($input, ['node' => $node], ['langcode' => $this->interfaceLanguage->getId()], $bubbleable_metadata);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
      $this->assertEquals($metadata_tests[$input], $bubbleable_metadata);
    }

    // Repeat for an unpublished node.
    $node = Node::create([
      'type' => 'article',
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
    ]);
    $node->setUnpublished();
    $node->save();

    // Generate and test tokens.
    $tests = [];
    $tests['[node:published_status]'] = 'Unpublished';

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated for unpublished node.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, ['node' => $node], ['language' => $this->interfaceLanguage]);
      $this->assertEquals($output, $expected, "Node token $input replaced for unpublished node.");
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
    $this->assertNotContains(0, array_map('strlen', $tests), 'No empty tokens generated for node without a summary.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, ['node' => $node], ['language' => $this->interfaceLanguage]);
      $this->assertSame((string) $expected, $output, "Failed test case: {$input}");
    }
  }

}
