<?php

namespace Drupal\node\Tests;

/**
 * Ensures that data added to nodes by other modules appears in RSS feeds.
 *
 * Create a node, enable the node_test module to ensure that extra data is
 * added to the node's renderable array, then verify that the data appears on
 * the site-wide RSS feed at rss.xml.
 *
 * @group node
 */
class NodeRSSContentTest extends NodeTestBase {

  /**
   * Enable a module that implements hook_node_view().
   *
   * @var array
   */
  public static $modules = array('node_test', 'views');

  protected function setUp() {
    parent::setUp();

    // Use bypass node access permission here, because the test class uses
    // hook_grants_alter() to deny access to everyone on node_access
    // queries.
    $user = $this->drupalCreateUser(array('bypass node access', 'access content', 'create article content'));
    $this->drupalLogin($user);
  }

  /**
   * Ensures that a new node includes the custom data when added to an RSS feed.
   */
  function testNodeRSSContent() {
    // Create a node.
    $node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));

    $this->drupalGet('rss.xml');

    // Check that content added in 'rss' view mode appear in RSS feed.
    $rss_only_content = t('Extra data that should appear only in the RSS feed for node @nid.', array('@nid' => $node->id()));
    $this->assertText($rss_only_content, 'Node content designated for RSS appear in RSS feed.');

    // Check that content added in view modes other than 'rss' doesn't
    // appear in RSS feed.
    $non_rss_content = t('Extra data that should appear everywhere except the RSS feed for node @nid.', array('@nid' => $node->id()));
    $this->assertNoText($non_rss_content, 'Node content not designed for RSS does not appear in RSS feed.');

    // Check that extra RSS elements and namespaces are added to RSS feed.
    $test_element = '<testElement>' . t('Value of testElement RSS element for node @nid.', array('@nid' => $node->id())) . '</testElement>';
    $test_ns = 'xmlns:drupaltest="http://example.com/test-namespace"';
    $this->assertRaw($test_element, 'Extra RSS elements appear in RSS feed.');
    $this->assertRaw($test_ns, 'Extra namespaces appear in RSS feed.');

    // Check that content added in 'rss' view mode doesn't appear when
    // viewing node.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoText($rss_only_content, 'Node content designed for RSS does not appear when viewing node.');
  }

}
