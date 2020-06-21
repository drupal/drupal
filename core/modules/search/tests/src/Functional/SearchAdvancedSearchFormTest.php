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
    $this->assertNotEqual($dummy_title, $this->node->label(), "Dummy title doesn't equal node title.");

    // Search for the dummy title with a GET query.
    $this->drupalGet('search/node', ['query' => ['keys' => $dummy_title]]);
    $this->assertNoText($this->node->label(), 'Basic page node is not found with dummy title.');

    // Search for the title of the node with a GET query.
    $this->drupalGet('search/node', ['query' => ['keys' => $this->node->label()]]);
    $this->assertText($this->node->label(), 'Basic page node is found with GET query.');

    // Search for the title of the node with a POST query.
    $edit = ['or' => $this->node->label()];
    $this->drupalPostForm('search/node', $edit, 'edit-submit--2');
    $this->assertText($this->node->label(), 'Basic page node is found with POST query.');

    // Search by node type.
    $this->drupalPostForm('search/node', array_merge($edit, ['type[page]' => 'page']), 'edit-submit--2');
    $this->assertText($this->node->label(), 'Basic page node is found with POST query and type:page.');

    $this->drupalPostForm('search/node', array_merge($edit, ['type[article]' => 'article']), 'edit-submit--2');
    $this->assertText('search yielded no results', 'Article node is not found with POST query and type:article.');
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
    $this->drupalPostForm('search/node', $edit, 'edit-submit--2');

    // Test that the encoded query appears in the page title. Only test the
    // part not including the quote, because assertText() cannot seem to find
    // the quote marks successfully.
    $this->assertText('Search for cat dog OR gerbil -fish -snake');

    // Verify that all of the form fields are filled out.
    foreach ($edit as $key => $value) {
      if ($key != 'type[page]') {
        $elements = $this->xpath('//input[@name=:name]', [':name' => $key]);
        $this->assertTrue(isset($elements[0]) && $elements[0]->getValue() == $value, "Field $key is set to $value");
      }
      else {
        $elements = $this->xpath('//input[@name=:name]', [':name' => $key]);
        $this->assertTrue(isset($elements[0]) && !empty($elements[0]->getAttribute('checked')), "Field $key is checked");
      }
    }

    // Now test by submitting the or/not part of the query in the main
    // search box, and verify that the advanced form is not filled out.
    // (It shouldn't be filled out unless you submit values in those fields.)
    $edit2 = ['keys' => 'cat dog OR gerbil -fish -snake'];
    $this->drupalPostForm('search/node', $edit2, 'edit-submit--2');
    $this->assertText('Search for cat dog OR gerbil -fish -snake');
    foreach ($edit as $key => $value) {
      if ($key != 'type[page]') {
        $elements = $this->xpath('//input[@name=:name]', [':name' => $key]);
        $this->assertFalse(isset($elements[0]) && $elements[0]->getValue() == $value, "Field $key is not set to $value");
      }
    }
  }

}
