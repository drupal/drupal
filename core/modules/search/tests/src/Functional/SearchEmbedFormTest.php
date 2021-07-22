<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies that a form embedded in search results works.
 *
 * @group search
 */
class SearchEmbedFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search', 'search_embedded_form'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Node used for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Count of how many times the form has been submitted.
   *
   * @var int
   */
  protected $submitCount = 0;

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a user and a node, and update the search index.
    $test_user = $this->drupalCreateUser([
      'access content',
      'search content',
      'administer nodes',
    ]);
    $this->drupalLogin($test_user);

    $this->node = $this->drupalCreateNode();

    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Set up a dummy initial count of times the form has been submitted.
    $this->submitCount = \Drupal::state()->get('search_embedded_form.submit_count');
    $this->refreshVariables();
  }

  /**
   * Tests that the embedded form appears and can be submitted.
   */
  public function testEmbeddedForm() {
    // First verify we can submit the form from the module's page.
    $this->drupalGet('search_embedded_form');
    $this->submitForm(['name' => 'John'], 'Send away');
    $this->assertSession()->pageTextContains('Test form was submitted');
    $count = \Drupal::state()->get('search_embedded_form.submit_count');
    $this->assertEquals($this->submitCount + 1, $count, 'Form submission count is correct');
    $this->submitCount = $count;

    // Now verify that we can see and submit the form from the search results.
    $this->drupalGet('search/node', ['query' => ['keys' => $this->node->label()]]);
    $this->assertSession()->pageTextContains('Your name');
    $this->submitForm(['name' => 'John'], 'Send away');
    $this->assertSession()->pageTextContains('Test form was submitted');
    $count = \Drupal::state()->get('search_embedded_form.submit_count');
    $this->assertEquals($this->submitCount + 1, $count, 'Form submission count is correct');
    $this->submitCount = $count;

    // Now verify that if we submit the search form, it doesn't count as
    // our form being submitted.
    $this->drupalGet('search');
    $this->submitForm(['keys' => 'foo'], 'Search');
    $this->assertSession()->pageTextNotContains('Test form was submitted');
    $count = \Drupal::state()->get('search_embedded_form.submit_count');
    $this->assertEquals($this->submitCount, $count, 'Form submission count is correct');
    $this->submitCount = $count;
  }

}
