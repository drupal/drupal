<?php

namespace Drupal\node\Tests;

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
  public static $modules = array('views');

  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(array('create article content', 'create page content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Creates four nodes and ensures that they are loaded correctly.
   */
  function testNodeMultipleLoad() {
    $node1 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $node2 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $node3 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 0));
    $node4 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0));

    // Confirm that promoted nodes appear in the default node listing.
    $this->drupalGet('node');
    $this->assertText($node1->label(), 'Node title appears on the default listing.');
    $this->assertText($node2->label(), 'Node title appears on the default listing.');
    $this->assertNoText($node3->label(), 'Node title does not appear in the default listing.');
    $this->assertNoText($node4->label(), 'Node title does not appear in the default listing.');

    // Load nodes with only a condition. Nodes 3 and 4 will be loaded.
    $nodes = entity_load_multiple_by_properties('node', array('promote' => 0));
    $this->assertEqual($node3->label(), $nodes[$node3->id()]->label(), 'Node was loaded.');
    $this->assertEqual($node4->label(), $nodes[$node4->id()]->label(), 'Node was loaded.');
    $count = count($nodes);
    $this->assertTrue($count == 2, format_string('@count nodes loaded.', array('@count' => $count)));

    // Load nodes by nid. Nodes 1, 2 and 4 will be loaded.
    $nodes = Node::loadMultiple(array(1, 2, 4));
    $count = count($nodes);
    $this->assertTrue(count($nodes) == 3, format_string('@count nodes loaded', array('@count' => $count)));
    $this->assertTrue(isset($nodes[$node1->id()]), 'Node is correctly keyed in the array');
    $this->assertTrue(isset($nodes[$node2->id()]), 'Node is correctly keyed in the array');
    $this->assertTrue(isset($nodes[$node4->id()]), 'Node is correctly keyed in the array');
    foreach ($nodes as $node) {
      $this->assertTrue(is_object($node), 'Node is an object');
    }
  }

}
