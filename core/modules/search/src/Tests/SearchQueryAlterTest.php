<?php

/**
 * @file
 * Contains Drupal\search\Tests\SearchQueryAlterTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests that the node search query can be altered via the query alter hook.
 *
 * @group search
 */
class SearchQueryAlterTest extends SearchTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('search_query_alter');

  /**
   * Tests that the query alter works.
   */
  function testQueryAlter() {
    // Login with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser(array('create page content', 'search content')));

    // Create a node and an article with the same keyword. The query alter
    // test module will alter the query so only articles should be returned.
    $data = array(
      'type' => 'page',
      'title' => 'test page',
      'body' => array(array('value' => 'pizza')),
    );
    $this->drupalCreateNode($data);

    $data['type'] = 'article';
    $data['title'] = 'test article';
    $this->drupalCreateNode($data);

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Search for the body keyword 'pizza'.
    $this->drupalPostForm('search/node', array('keys' => 'pizza'), t('Search'));
    // The article should be there but not the page.
    $this->assertText('article', 'Article is in search results');
    $this->assertNoText('page', 'Page is not in search results');
  }
}
