<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeFieldTokensTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\views\Views;
use Drupal\node\Tests\Views\NodeTestBase;

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
  public static $testViews = array('test_node_tokens');

  /**
   * Tests token replacement for Views tokens supplied by the Node module.
   */
  public function testViewsTokenReplacement() {
    // Create the Article content type with a standard body field.
    /* @var $node_type \Drupal\node\NodeTypeInterface */
    $node_type = entity_create('node_type', ['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);

    // Create a user and a node.
    $account = $this->createUser();
    $body = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);

    /** @var $node \Drupal\node\NodeInterface */
    $node = entity_create('node', [
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
