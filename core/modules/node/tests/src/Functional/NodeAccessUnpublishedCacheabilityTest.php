<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\node\NodeInterface;

/**
 * Tests cacheability on unpublished nodes inherited from node access.
 *
 * @group node
 * @group Cache
 */
class NodeAccessUnpublishedCacheabilityTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node_access_test_auto_bubbling',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests correct cacheability information bubbles up from node access.
   */
  public function testNodeAccessCacheabilityBubbleUpOnUnpublishedContent(): void {
    $rid = $this->drupalCreateRole([
      'access content',
      'view own unpublished content',
    ]);
    $test_user1 = $this->drupalCreateUser(values: ['roles' => [$rid]]);
    $test_user2 = $this->drupalCreateUser(values: ['roles' => [$rid]]);

    $unpublished_node_by_test_user1 = $this->createNode(['type' => 'page', 'uid' => $test_user1->id(), 'status' => NodeInterface::NOT_PUBLISHED]);

    $this->drupalLogin($test_user2);
    $this->drupalGet('node_access_test_auto_bubbling_node_access/' . $unpublished_node_by_test_user1->id());
    $this->assertSession()->pageTextNotContains($unpublished_node_by_test_user1->label());

    // The author of the unpublished node must have access.
    $this->drupalLogin($test_user1);
    $this->drupalGet('node_access_test_auto_bubbling_node_access/' . $unpublished_node_by_test_user1->id());
    $this->assertSession()->pageTextContains($unpublished_node_by_test_user1->label());
  }

}
