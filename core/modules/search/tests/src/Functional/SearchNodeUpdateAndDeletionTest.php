<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional;

use Drupal\Core\Database\Database;
use Drupal\search\SearchIndexInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests search index is updated properly when nodes are removed or updated.
 *
 * @group search
 */
class SearchNodeUpdateAndDeletionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to access and search content.
   *
   * @var \Drupal\user\UserInterface
   */
  public $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a test user and log in.
    $this->testUser = $this->drupalCreateUser([
      'access content',
      'search content',
    ]);
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests that the search index info is properly updated when a node changes.
   */
  public function testSearchIndexUpdateOnNodeChange(): void {
    // Create a node.
    $node = $this->drupalCreateNode([
      'title' => 'Someone who says Ni!',
      'body' => [['value' => "We are the knights who say Ni!"]],
      'type' => 'page',
    ]);

    $node_search_plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    // Update the search index.
    $node_search_plugin->updateIndex();
    $search_index = \Drupal::service('search.index');
    assert($search_index instanceof SearchIndexInterface);

    // Search the node to verify it appears in search results
    $edit = ['keys' => 'knights'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertSession()->pageTextContains($node->label());

    // Update the node
    $node->body->value = "We want a shrubbery!";
    $node->save();

    // Run indexer again
    $node_search_plugin->updateIndex();

    // Search again to verify the new text appears in test results.
    $edit = ['keys' => 'shrubbery'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertSession()->pageTextContains($node->label());
  }

  /**
   * Tests that the search index info is updated when a node is deleted.
   */
  public function testSearchIndexUpdateOnNodeDeletion(): void {
    // Create a node.
    $node = $this->drupalCreateNode([
      'title' => 'No dragons here',
      'body' => [['value' => 'Again: No dragons here']],
      'type' => 'page',
    ]);

    $node_search_plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    // Update the search index.
    $node_search_plugin->updateIndex();

    // Search the node to verify it appears in search results
    $edit = ['keys' => 'dragons'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertSession()->pageTextContains($node->label());

    // Get the node info from the search index tables.
    $connection = Database::getConnection();
    $search_index_dataset = $connection->select('search_index', 'si')
      ->fields('si', ['sid'])
      ->condition('type', 'node_search')
      ->condition('word', 'dragons')
      ->execute()
      ->fetchField();
    $this->assertNotFalse($search_index_dataset, 'Node info found on the search_index');

    // Delete the node.
    $node->delete();

    // Check if the node info is gone from the search table.
    $search_index_dataset = $connection->select('search_index', 'si')
      ->fields('si', ['sid'])
      ->condition('type', 'node_search')
      ->condition('word', 'dragons')
      ->execute()
      ->fetchField();
    $this->assertFalse($search_index_dataset, 'Node info successfully removed from search_index');

    // Search again to verify the node doesn't appear anymore.
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertSession()->pageTextNotContains($node->label());
  }

}
