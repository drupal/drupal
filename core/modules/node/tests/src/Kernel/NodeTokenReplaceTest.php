<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\system\Kernel\Token\TokenReplaceKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node token replacement.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeTokenReplaceTest extends TokenReplaceKernelTestBase {

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

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  public function testNodeTokenReplacement(): void {
    $url_options = [
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    ];

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
          'format' => 'plain_text',
        ],
      ],
    ]);
    $node->save();

    // Generate and test tokens.
    $tests = [];
    $tests['[node:nid]'] = $node->id();
    $tests['[node:uuid]'] = $node->uuid();
    $tests['[node:vid]'] = $node->getRevisionId();
    $tests['[node:type]'] = 'article';
    $tests['[node:type-name]'] = 'Article';
    $tests['[node:title]'] = Html::escape($node->getTitle());
    $tests['[node:body]'] = $node->body->processed;
    $tests['[node:langcode]'] = $node->language()->getId();
    $tests['[node:published_status]'] = 'Published';
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
    $metadata_tests['[node:uuid]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:vid]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:type]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:type-name]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:title]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:body]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:langcode]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:published_status]'] = $base_bubbleable_metadata;
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

    // Generate and test token - use full body as expected value.
    $tests = [];
    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, ['node' => $node], ['language' => $this->interfaceLanguage]);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
    }
  }

}
