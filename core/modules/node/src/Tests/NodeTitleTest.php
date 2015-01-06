<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTitleTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests node title.
 *
 * @group node
 */
class NodeTitleTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'views');

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer nodes', 'create article content', 'create page content', 'post comments'));
    $this->drupalLogin($this->adminUser);
    $this->container->get('comment.manager')->addDefaultField('node', 'page');
  }

  /**
   *  Creates one node and tests if the node title has the correct value.
   */
  function testNodeTitle() {
    // Create "Basic page" content with title.
    // Add the node to the frontpage so we can test if teaser links are
    // clickable.
    $settings = array(
      'title' => $this->randomMachineName(8),
      'promote' => 1,
    );
    $node = $this->drupalCreateNode($settings);

    // Test <title> tag.
    $this->drupalGet('node/' . $node->id());
    $xpath = '//title';
    $this->assertEqual(current($this->xpath($xpath)), $node->label() .' | Drupal', 'Page title is equal to node title.', 'Node');

    // Test breadcrumb in comment preview.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment');
    $xpath = '//nav[@class="breadcrumb"]/ol/li[last()]/a';
    $this->assertEqual(current($this->xpath($xpath)), $node->label(), 'Node breadcrumb is equal to node title.', 'Node');

    // Test node title in comment preview.
    $this->assertEqual(current($this->xpath('//article[contains(concat(" ", normalize-space(@class), " "), :node-class)]/h2/a/span', array(':node-class' => ' node--type-' . $node->bundle() . ' '))), $node->label(), 'Node preview title is equal to node title.', 'Node');

    // Test node title is clickable on teaser list (/node).
    $this->drupalGet('node');
    $this->clickLink($node->label());

    // Test edge case where node title is set to 0.
    $settings = array(
      'title' => 0,
    );
    $node = $this->drupalCreateNode($settings);
    // Test that 0 appears as <title>.
    $this->drupalGet('node/' . $node->id());
    $this->assertTitle(0 . ' | Drupal', 'Page title is equal to 0.', 'Node');
    // Test that 0 appears in the template <h1>.
    $xpath = '//h1';
    $this->assertEqual(current($this->xpath($xpath)), 0, 'Node title is displayed as 0.', 'Node');
  }
}
