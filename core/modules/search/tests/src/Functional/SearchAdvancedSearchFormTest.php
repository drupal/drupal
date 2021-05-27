<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Indexes content and tests the advanced search form.
 *
 * @group search
 */
class SearchAdvancedSearchFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A node to use for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create and log in user.
    $test_user = $this->drupalCreateUser([
      'access content',
      'search content',
      'use advanced search',
      'administer nodes',
    ]);
    $this->drupalLogin($test_user);

    // Create initial node.
    $this->node = $this->drupalCreateNode();

    // First update the index. This does the initial processing.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
  }

  /**
   * Tests advanced search by node type.
   */
  public function testNodeType() {
    // Verify some properties of the node that was created.
    $this->assertTrue($this->node->getType() == 'page', 'Node type is Basic page.');
    $dummy_title = 'Lorem ipsum';
    $this->assertNotEquals($dummy_title, $this->node->label(), "Dummy title doesn't equal node title.");

    // Search for the dummy title with a GET query.
    $this->drupalGet('search/node', ['query' => ['keys' => $dummy_title]]);
    $this->assertNoText($this->node->label());

    // Search for the title of the node with a GET query.
    $this->drupalGet('search/node', ['query' => ['keys' => $this->node->label()]]);
    $this->assertSession()->pageTextContains($this->node->label());

    // Search for the title of the node with a POST query.
    $edit = ['or' => $this->node->label()];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'edit-submit--2');
    $this->assertSession()->pageTextContains($this->node->label());

    // Search by node type.
    $this->drupalGet('search/node');
    $this->submitForm(array_merge($edit, ['type[page]' => 'page']), 'edit-submit--2');
    $this->assertSession()->pageTextContains($this->node->label());

    $this->drupalGet('search/node');
    $this->submitForm(array_merge($edit, ['type[article]' => 'article']), 'edit-submit--2');
    $this->assertSession()->pageTextContains('search yielded no results');
  }

  /**
   * Tests that after submitting the advanced search form, the form is refilled.
   */
  public function testFormRefill() {
    $edit = [
      'keys' => 'cat',
      'or' => 'dog gerbil',
      'phrase' => 'pets are nice',
      'negative' => 'fish snake',
      'type[page]' => 'page',
    ];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'edit-submit--2');

    // Test that the encoded query appears in the page title. Only test the
    // part not including the quote, because assertText() cannot seem to find
    // the quote marks successfully.
    $this->assertSession()->pageTextContains('Search for cat dog OR gerbil -fish -snake');

    // Verify that all of the form fields are filled out.
    foreach ($edit as $key => $value) {
      if ($key != 'type[page]') {
        $this->assertSession()->fieldValueEquals($key, $value);
      }
      else {
        $this->assertSession()->checkboxChecked($key);
      }
    }

    // Now test by submitting the or/not part of the query in the main
    // search box, and verify that the advanced form is not filled out.
    // (It shouldn't be filled out unless you submit values in those fields.)
    $edit2 = ['keys' => 'cat dog OR gerbil -fish -snake'];
    $this->drupalGet('search/node');
    $this->submitForm($edit2, 'edit-submit--2');
    $this->assertSession()->pageTextContains('Search for cat dog OR gerbil -fish -snake');
    foreach ($edit as $key => $value) {
      if ($key != 'type[page]') {
        $this->assertSession()->fieldValueNotEquals($key, $value);
      }
    }
  }

}
