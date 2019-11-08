<?php

namespace Drupal\Tests\node\Functional\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests replacement of Views tokens supplied by the Node module.
 *
 * @group node
 * @see \Drupal\node\Tests\NodeTokenReplaceTest
 */
class NodeFieldTokensTest extends NodeTestBase {

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
  public function testViewsTokenReplacement() {
    // Create the Article content type with a standard body field.
    /* @var $node_type \Drupal\node\NodeTypeInterface */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);

    // Create a user and a node.
    $account = $this->createUser();
    $body = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);

    /** @var $node \Drupal\node\NodeInterface */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => $account->id(),
      'title' => 'Testing Views tokens',
      'body' => [['value' => $body, 'summary' => $summary, 'format' => 'plain_text']],
    ]);
    $node->save();

    $this->drupalGet('test_node_tokens');

    // Body: {{ body }}<br />
    $this->assertRaw("Body: <p>$body</p>");

    // Raw value: {{ body__value }}<br />
    $this->assertRaw("Raw value: $body");

    // Raw summary: {{ body__summary }}<br />
    $this->assertRaw("Raw summary: $summary");

    // Raw format: {{ body__format }}<br />
    $this->assertRaw("Raw format: plain_text");
  }

}
