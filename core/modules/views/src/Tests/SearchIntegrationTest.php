<?php

/**
 * @file
 * Contains \Drupal\views\Tests\SearchIntegrationTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests search integration filters.
 *
 * @group views
 */
class SearchIntegrationTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'search');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_search');

  /**
   * Tests search integration.
   */
  public function testSearchIntegration() {
    // Create a content type.
    $type = $this->drupalCreateContentType();

    // Add two nodes, one containing the word "pizza" and the other
    // with the word "sandwich". Make the second node link to the first.
    $node['title'] = 'pizza';
    $node['body'] = array(array('value' => 'pizza'));
    $node['type'] = $type->type;
    $this->drupalCreateNode($node);

    $this->drupalGet('node/1');
    $node_url = $this->getUrl();

    $node['title'] = 'sandwich';
    $node['body'] = array(array('value' => 'sandwich with a <a href="' . $node_url . '">link to first node</a>'));
    $this->drupalCreateNode($node);

    // Run cron so that the search index tables are updated.
    $this->cronRun();

    // Test the various views filters by visiting their pages.
    // These are in the test view 'test_search', and they just display the
    // titles of the nodes in the result, as links.

    // Page with a keyword filter of 'pizza'.
    $this->drupalGet('test-filter');
    $this->assertLink('pizza', 0, 'Pizza page is on Filter page');
    $this->assertNoLink('sandwich', 'Sandwich page is not on Filter page');

    // Page with a keyword argument.
    $this->drupalGet('test-arg/pizza');
    $this->assertLink('pizza', 0, 'Pizza page is on argument page');
    $this->assertNoLink('sandwich', 'Sandwich page is not on argument page');

    $this->drupalGet('test-arg/sandwich');
    $this->assertNoLink('pizza', 'Pizza page is not on argument page');
    $this->assertLink('sandwich', 0, 'Sandwich page is on argument page');
  }

}
