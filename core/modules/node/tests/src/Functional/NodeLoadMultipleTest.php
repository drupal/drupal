<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests the loading of multiple nodes.
 *
 * @group node
 */
class NodeLoadMultipleTest extends NodeTestBase {

  /**
   * Enable Views to test the frontpage against Node::loadMultiple() results.
   *
   * @var array
   */
  public static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser([
      'create article content',
      'create page content',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Creates four nodes and ensures that they are loaded correctly.
   */
  public function testNodeMultipleLoad() {
    $node1 = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $node2 = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $node3 = $this->drupalCreateNode(['type' => 'article', 'promote' => 0]);
    $node4 = $this->drupalCreateNode(['type' => 'page', 'promote' => 0]);

    // Confirm that promoted nodes appear in the default node listing.
    $this->drupalGet('node');
    $this->assertText($node1->label(), 'Node title appears on the default listing.');
    $this->assertText($node2->label(), 'Node title appears on the default listing.');
    $this->assertNoText($node3->label(), 'Node title does not appear in the default listing.');
    $this->assertNoText($node4->label(), 'Node title does not appear in the default listing.');
    // Load nodes with only a condition. Nodes 3 and 4 will be loaded.
    $nodes = $this->container->get('entity_type.manager')->getStorage('node')
      ->loadByProperties(['promote' => 0]);
    $this->assertEqual($node3->label(), $nodes[$node3->id()]->label(), 'Node was loaded.');
    $this->assertEqual($node4->label(), $nodes[$node4->id()]->label(), 'Node was loaded.');
    $this->assertCount(2, $nodes);

    // Load nodes by nid. Nodes 1, 2 and 4 will be loaded.
    $nodes = Node::loadMultiple([1, 2, 4]);
    $this->assertCount(3, $nodes);
    $this->assertTrue(isset($nodes[$node1->id()]), 'Node is correctly keyed in the array');
    $this->assertTrue(isset($nodes[$node2->id()]), 'Node is correctly keyed in the array');
    $this->assertTrue(isset($nodes[$node4->id()]), 'Node is correctly keyed in the array');
    foreach ($nodes as $node) {
      $this->assertIsObject($node);
    }
  }

}
