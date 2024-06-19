<?php

declare(strict_types=1);

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
  public function testPreprocessLangcode(): void {
    // Create a node.
    $this->node = $this->drupalCreateNode(['body' => [[]], 'langcode' => 'en']);

    // First update the index. This does the initial processing.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Search for the additional text that is added by the preprocess
    // function. If you search for text that is in the node, preprocess is
    // not invoked on the node during the search excerpt generation.
    $edit = ['or' => 'Additional text'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'edit-submit--2');

    // Checks if the langcode message has been set by hook_search_preprocess().
    $this->assertSession()->pageTextContains('Langcode Preprocess Test: en');
  }

  /**
   * Tests stemming for hook_search_preprocess().
   */
  public function testPreprocessStemming(): void {
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
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'edit-submit--2');

    // Check if the node has been found.
    $this->assertSession()->pageTextContains('Search results');
    $this->assertSession()->pageTextContains('we are testing');

    // Search for the same node using a different query.
    $edit = ['or' => 'test'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'edit-submit--2');

    // Check if the node has been found.
    $this->assertSession()->pageTextContains('Search results');
    $this->assertSession()->pageTextContains('we are testing');
  }

}
