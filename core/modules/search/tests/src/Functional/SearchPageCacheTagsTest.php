<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the search_page entity cache tags on the search results pages.
 *
 * @group search
 */
class SearchPageCacheTagsTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

  /**
   * A user with permission to search content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchingUser;

  /**
   * A node that is indexed by the search module.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create user.
    $this->searchingUser = $this->drupalCreateUser([
      'search content',
      'access user profiles',
    ]);

    // Create a node and update the search index.
    $this->node = $this->drupalCreateNode(['title' => 'bike shed shop']);
    $this->node->setOwner($this->searchingUser);
    $this->node->save();
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
  }

  /**
   * Tests the presence of the expected cache tag in various situations.
   */
  public function testSearchText() {
    $this->drupalLogin($this->searchingUser);

    // Initial page for searching nodes.
    $this->drupalGet('search/node');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index:node_search');
    $this->assertCacheTag('node_list');

    // Node search results.
    $edit = [];
    $edit['keys'] = 'bike shed';
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('bike shed shop');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index');
    $this->assertCacheTag('search_index:node_search');
    $this->assertCacheTag('node:1');
    $this->assertCacheTag('user:2');
    $this->assertCacheTag('rendered');
    $this->assertCacheTag('http_response');
    $this->assertCacheTag('node_list');

    // Updating a node should invalidate the search plugin's index cache tag.
    $this->node->title = 'bike shop';
    $this->node->save();
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('bike shop');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index');
    $this->assertCacheTag('search_index:node_search');
    $this->assertCacheTag('node:1');
    $this->assertCacheTag('user:2');
    $this->assertCacheTag('rendered');
    $this->assertCacheTag('http_response');
    $this->assertCacheTag('node_list');

    // Deleting a node should invalidate the search plugin's index cache tag.
    $this->node->delete();
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('Your search yielded no results.');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index');
    $this->assertCacheTag('search_index:node_search');
    $this->assertCacheTag('node_list');

    // Initial page for searching users.
    $this->drupalGet('search/user');
    $this->assertCacheTag('config:search.page.user_search');
    $this->assertCacheTag('user_list');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'search_index');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'search_index:user_search');

    // User search results.
    $edit['keys'] = $this->searchingUser->getAccountName();
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertCacheTag('config:search.page.user_search');
    $this->assertCacheTag('user_list');
    $this->assertCacheTag('user:2');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'search_index');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'search_index:user_search');
  }

  /**
   * Tests the presence of expected cache tags with referenced entities.
   */
  public function testSearchTagsBubbling() {

    // Install field UI and entity reference modules.
    $this->container->get('module_installer')->install(['field_ui', 'entity_reference']);
    $this->resetAll();

    // Creates a new content type that will have an entity reference.
    $type_name = 'entity_reference_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);

    $bundle_path = 'admin/structure/types/manage/' . $type->id();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'create ' . $type_name . ' content',
      'administer node fields',
      'administer node display',

    ]);
    $this->drupalLogin($admin_user);

    // First step: 'Add new field' on the 'Manage fields' page.
    $this->drupalGet($bundle_path . '/fields/add-field');
    $this->drupalPostForm(NULL, [
      'label' => 'Test label',
      'field_name' => 'test__ref',
      'new_storage_type' => 'entity_reference',
    ], t('Save and continue'));

    // Second step: 'Field settings' form.
    $this->drupalPostForm(NULL, [], t('Save field settings'));

    // Create a new node of our newly created node type and fill in the entity
    // reference field.
    $edit = [
      'title[0][value]' => 'Llama shop',
      'field_test__ref[0][target_id]' => $this->node->getTitle(),
    ];
    $this->drupalPostForm('node/add/' . $type->id(), $edit, t('Save'));

    // Test that the value of the entity reference field is shown.
    $this->drupalGet('node/2');
    $this->assertText('bike shed shop');

    // Refresh the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Log in with searching user again.
    $this->drupalLogin($this->searchingUser);

    // Default search cache tags.
    $default_search_tags = [
      'config:search.page.node_search',
      'search_index',
      'search_index:node_search',
      'http_response',
      'rendered',
      'node_list',
    ];

    // Node search results for shop, should return node:1 (bike shed shop) and
    // node:2 (Llama shop). The related authors cache tags should be visible as
    // well.
    $edit = [];
    $edit['keys'] = 'shop';
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('bike shed shop');
    $this->assertText('Llama shop');
    $expected_cache_tags = Cache::mergeTags($default_search_tags, [
      'node:1',
      'user:2',
      'node:2',
      'user:3',
      'node_view',
      'config:filter.format.plain_text',
    ]);
    $this->assertCacheTags($expected_cache_tags);

    // Only get the new node in the search results, should result in node:1,
    // node:2 and user:3 as cache tags even though only node:1 is shown. This is
    // because node:2 is reference in node:1 as an entity reference.
    $edit = [];
    $edit['keys'] = 'Llama';
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('Llama shop');
    $expected_cache_tags = Cache::mergeTags($default_search_tags, [
      'node:1',
      'node:2',
      'user:3',
      'node_view',
    ]);
    $this->assertCacheTags($expected_cache_tags);
  }

}
