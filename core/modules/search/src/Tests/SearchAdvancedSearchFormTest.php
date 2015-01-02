<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchAdvancedSearchFormTest.
 */

namespace Drupal\search\Tests;

/**
 * Indexes content and tests the advanced search form.
 *
 * @group search
 */
class SearchAdvancedSearchFormTest extends SearchTestBase {

  /**
   * A node to use for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  protected function setUp() {
    parent::setUp();
    // Create and login user.
    $test_user = $this->drupalCreateUser(array('access content', 'search content', 'use advanced search', 'administer nodes'));
    $this->drupalLogin($test_user);

    // Create initial node.
    $this->node = $this->drupalCreateNode();

    // First update the index. This does the initial processing.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Then, run the shutdown function. Testing is a unique case where indexing
    // and searching has to happen in the same request, so running the shutdown
    // function manually is needed to finish the indexing process.
    search_update_totals();
  }

  /**
   * Test using the search form with GET and POST queries.
   * Test using the advanced search form to limit search to nodes of type "Basic page".
   */
  function testNodeType() {
    $this->assertTrue($this->node->getType() == 'page', 'Node type is Basic page.');

    // Assert that the dummy title doesn't equal the real title.
    $dummy_title = 'Lorem ipsum';
    $this->assertNotEqual($dummy_title, $this->node->label(), "Dummy title doesn't equal node title.");

    // Search for the dummy title with a GET query.
    $this->drupalGet('search/node', array('query' => array('keys' => $dummy_title)));
    $this->assertNoText($this->node->label(), 'Basic page node is not found with dummy title.');

    // Search for the title of the node with a GET query.
    $this->drupalGet('search/node', array('query' => array('keys' => $this->node->label())));
    $this->assertText($this->node->label(), 'Basic page node is found with GET query.');

    // Search for the title of the node with a POST query.
    $edit = array('or' => $this->node->label());
    $this->drupalPostForm('search/node', $edit, t('Advanced search'));
    $this->assertText($this->node->label(), 'Basic page node is found with POST query.');

    // Advanced search type option.
    $this->drupalPostForm('search/node', array_merge($edit, array('type[page]' => 'page')), t('Advanced search'));
    $this->assertText($this->node->label(), 'Basic page node is found with POST query and type:page.');

    $this->drupalPostForm('search/node', array_merge($edit, array('type[article]' => 'article')), t('Advanced search'));
    $this->assertText('bike shed', 'Article node is not found with POST query and type:article.');
  }
}
