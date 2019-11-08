<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the search preprocessing uses the correct language code.
 *
 * @group search
 */
class SearchPreprocessLangcodeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search', 'search_langcode_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test node for searching.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'search content',
      'use advanced search',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests that hook_search_preprocess() returns the correct langcode.
   */
  public function testPreprocessLangcode() {
    // Create a node.
    $this->node = $this->drupalCreateNode(['body' => [[]], 'langcode' => 'en']);

    // First update the index. This does the initial processing.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Search for the additional text that is added by the preprocess
    // function. If you search for text that is in the node, preprocess is
    // not invoked on the node during the search excerpt generation.
    $edit = ['or' => 'Additional text'];
    $this->drupalPostForm('search/node', $edit, 'edit-submit--2');

    // Checks if the langcode message has been set by hook_search_preprocess().
    $this->assertText('Langcode Preprocess Test: en');
  }

  /**
   * Tests stemming for hook_search_preprocess().
   */
  public function testPreprocessStemming() {
    // Create a node.
    $this->node = $this->drupalCreateNode([
      'title' => 'we are testing',
      'body' => [[]],
      'langcode' => 'en',
    ]);

    // First update the index. This does the initial processing.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Search for the title of the node with a POST query.
    $edit = ['or' => 'testing'];
    $this->drupalPostForm('search/node', $edit, 'edit-submit--2');

    // Check if the node has been found.
    $this->assertText('Search results');
    $this->assertText('we are testing');

    // Search for the same node using a different query.
    $edit = ['or' => 'test'];
    $this->drupalPostForm('search/node', $edit, 'edit-submit--2');

    // Check if the node has been found.
    $this->assertText('Search results');
    $this->assertText('we are testing');
  }

}
