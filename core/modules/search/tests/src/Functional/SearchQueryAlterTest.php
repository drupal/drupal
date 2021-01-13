<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the node search query can be altered via the query alter hook.
 *
 * @group search
 */
class SearchQueryAlterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search', 'search_query_alter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the query alter works.
   */
  public function testQueryAlter() {
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Log in with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser([
      'create page content',
      'search content',
    ]));

    // Create a node and an article with the same keyword. The query alter
    // test module will alter the query so only articles should be returned.
    $data = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [['value' => 'pizza']],
    ];
    $this->drupalCreateNode($data);

    $data['type'] = 'article';
    $data['title'] = 'test article';
    $this->drupalCreateNode($data);

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Search for the body keyword 'pizza'.
    $this->drupalPostForm('search/node', ['keys' => 'pizza'], 'Search');
    // The article should be there but not the page.
    $this->assertText('article');
    $this->assertNoText('page');
  }

}
