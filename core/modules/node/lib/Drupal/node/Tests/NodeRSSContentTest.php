<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeRSSContentTest.
 */

namespace Drupal\node\Tests;

/**
 * Ensure that data added to nodes by other modules appears in RSS feeds.
 *
 * Create a node, enable the node_test module to ensure that extra data is
 * added to the node->content array, then verify that the data appears on the
 * sitewide RSS feed at rss.xml.
 */
class NodeRSSContentTest extends NodeTestBase {

  /**
   * Enable a module that implements hook_node_view().
   *
   * @var array
   */
  public static $modules = array('node_test');

  public static function getInfo() {
    return array(
      'name' => 'Node RSS Content',
      'description' => 'Ensure that data added to nodes by other modules appears in RSS feeds.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Use bypass node access permission here, because the test class uses
    // hook_grants_alter() to deny access to everyone on node_access
    // queries.
    $user = $this->drupalCreateUser(array('bypass node access', 'access content', 'create article content'));
    $this->drupalLogin($user);
  }

  /**
   * Create a new node and ensure that it includes the custom data when added
   * to an RSS feed.
   */
  function testNodeRSSContent() {
    // Create a node.
    $node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));

    $this->drupalGet('rss.xml');

    // Check that content added in 'rss' view mode appear in RSS feed.
    $rss_only_content = t('Extra data that should appear only in the RSS feed for node !nid.', array('!nid' => $node->nid));
    $this->assertText($rss_only_content, t('Node content designated for RSS appear in RSS feed.'));

    // Check that content added in view modes other than 'rss' doesn't
    // appear in RSS feed.
    $non_rss_content = t('Extra data that should appear everywhere except the RSS feed for node !nid.', array('!nid' => $node->nid));
    $this->assertNoText($non_rss_content, t('Node content not designed for RSS doesn\'t appear in RSS feed.'));

    // Check that extra RSS elements and namespaces are added to RSS feed.
    $test_element = array(
      'key' => 'testElement',
      'value' => t('Value of testElement RSS element for node !nid.', array('!nid' => $node->nid)),
    );
    $test_ns = 'xmlns:drupaltest="http://example.com/test-namespace"';
    $this->assertRaw(format_xml_elements(array($test_element)), t('Extra RSS elements appear in RSS feed.'));
    $this->assertRaw($test_ns, t('Extra namespaces appear in RSS feed.'));

    // Check that content added in 'rss' view mode doesn't appear when
    // viewing node.
    $this->drupalGet("node/$node->nid");
    $this->assertNoText($rss_only_content, t('Node content designed for RSS doesn\'t appear when viewing node.'));

    // Check that the node feed page does not try to interpret additional path
    // components as arguments for node_feed() and returns default content.
    $this->drupalGet('rss.xml/' . $this->randomName() . '/' . $this->randomName());
    $this->assertText($rss_only_content, t('Ignore page arguments when delivering rss.xml.'));
  }
}
