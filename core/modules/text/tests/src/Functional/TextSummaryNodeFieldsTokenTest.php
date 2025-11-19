<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Functional\Views\NodeTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests replacement of Views tokens supplied by the Node module.
 *
 * @see \Drupal\node\Tests\NodeTokenReplaceTest
 */
#[Group('text')]
#[RunTestsInSeparateProcesses]
class TextSummaryNodeFieldsTokenTest extends NodeTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_tokens'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests token replacement for Views tokens supplied by the Node module.
   */
  public function testViewsTokenReplacement(): void {
    // Create the Article content type with a standard body field.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article'], FALSE);

    FieldStorageConfig::loadByName('node', 'body')->delete();
    FieldStorageConfig::create([
      'field_name' => 'body',
      'type' => 'text_with_summary',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();

    $fieldStorage = FieldStorageConfig::loadByName('node', 'body');
    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'article',
      'label' => 'Body Test',
      'settings' => [
        'display_summary' => TRUE,
        'allowed_formats' => [],
      ],
    ])->save();

    // Create a user and a node.
    $account = $this->createUser();
    $body = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'uid' => $account->id(),
      'title' => 'Testing Views tokens',
      'body' => [['value' => $body, 'summary' => $summary, 'format' => 'plain_text']],
    ]);
    $node->save();

    $this->drupalGet('test_node_tokens');

    // Body: "{{ body }}<br />".
    $this->assertSession()->responseContains("Body: <p>$body</p>");

    // Raw value: "{{ body__value }}<br />".
    $this->assertSession()->responseContains("Raw value: $body");

    // Raw summary: "{{ body__summary }}<br />".
    $this->assertSession()->responseContains("Raw summary: $summary");

    // Raw format: "{{ body__format }}<br />".
    $this->assertSession()->responseContains("Raw format: plain_text");
  }

}
