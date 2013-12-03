<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTitleTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests node title functionality.
 */
class NodeTitleTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'views');

  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Node title',
      'description' => 'Test node title.',
      'group' => 'Node'
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer nodes', 'create article content', 'create page content', 'post comments'));
    $this->drupalLogin($this->admin_user);
    $this->container->get('comment.manager')->addDefaultField('node', 'page');
  }

  /**
   *  Creates one node and tests if the node title has the correct value.
   */
  function testNodeTitle() {
    // Create "Basic page" content with title.
    // Add the node to the frontpage so we can test if teaser links are clickable.
    $settings = array(
      'title' => $this->randomName(8),
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
    $this->assertEqual(current($this->xpath('//article[@id=:id]/h2/a/span', array(':id' => 'node-' . $node->id()))), $node->label(), 'Node preview title is equal to node title.', 'Node');

    // Test node title is clickable on teaser list (/node).
    $this->drupalGet('node');
    $this->clickLink($node->label());
  }
}
