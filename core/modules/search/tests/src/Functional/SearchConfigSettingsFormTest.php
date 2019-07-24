<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search\Entity\SearchPage;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify the search config settings form.
 *
 * @group search
 */
class SearchConfigSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'dblog', 'node', 'search', 'search_extra_type', 'test_page_test'];

  /**
   * User who can search and administer search.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchUser;

  /**
   * Node indexed for searching.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $searchNode;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Log in as a user that can create and search content.
    $this->searchUser = $this->drupalCreateUser(['search content', 'administer search', 'administer nodes', 'bypass node access', 'access user profiles', 'administer users', 'administer blocks', 'access site reports']);
    $this->drupalLogin($this->searchUser);

    // Add a single piece of content and index it.
    $node = $this->drupalCreateNode();
    $this->searchNode = $node;
    // Link the node to itself to test that it's only indexed once. The content
    // also needs the word "pizza" so we can use it as the search keyword.
    $body_key = 'body[0][value]';
    $edit[$body_key] = Link::fromTextAndUrl($node->label(), $node->toUrl())->toString() . ' pizza sandwich';
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Enable the search block.
    $this->drupalPlaceBlock('search_form_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Verifies the search settings form.
   */
  public function testSearchSettingsPage() {

    // Test that the settings form displays the correct count of items left to index.
    $this->drupalGet('admin/config/search/pages');
    $this->assertText(t('There are @count items left to index.', ['@count' => 0]));

    // Test the re-index button.
    $this->drupalPostForm('admin/config/search/pages', [], t('Re-index site'));
    $this->assertText(t('Are you sure you want to re-index the site'));
    $this->drupalPostForm('admin/config/search/pages/reindex', [], t('Re-index site'));
    $this->assertText(t('All search indexes will be rebuilt'));
    $this->drupalGet('admin/config/search/pages');
    $this->assertText(t('There is 1 item left to index.'));

    // Test that the form saves with the default values.
    $this->drupalPostForm('admin/config/search/pages', [], t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Form saves with the default values.');

    // Test that the form does not save with an invalid word length.
    $edit = [
      'minimum_word_size' => $this->randomMachineName(3),
    ];
    $this->drupalPostForm('admin/config/search/pages', $edit, t('Save configuration'));
    $this->assertNoText(t('The configuration options have been saved.'), 'Form does not save with an invalid word length.');

    // Test logging setting. It should be off by default.
    $text = $this->randomMachineName(5);
    $this->drupalPostForm('search/node', ['keys' => $text], t('Search'));
    $this->drupalGet('admin/reports/dblog');
    $this->assertNoLink('Searched Content for ' . $text . '.', 'Search was not logged');

    // Turn on logging.
    $edit = ['logging' => TRUE];
    $this->drupalPostForm('admin/config/search/pages', $edit, t('Save configuration'));
    $text = $this->randomMachineName(5);
    $this->drupalPostForm('search/node', ['keys' => $text], t('Search'));
    $this->drupalGet('admin/reports/dblog');
    $this->assertLink('Searched Content for ' . $text . '.', 0, 'Search was logged');

  }

  /**
   * Verifies plugin-supplied settings form.
   */
  public function testSearchModuleSettingsPage() {
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink(t('Edit'), 1);

    // Ensure that the default setting was picked up from the default config
    $this->assertTrue($this->xpath('//select[@id="edit-extra-type-settings-boost"]//option[@value="bi" and @selected="selected"]'), 'Module specific settings are picked up from the default config');

    // Change extra type setting and also modify a common search setting.
    $edit = [
      'extra_type_settings[boost]' => 'ii',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save search page'));

    // Ensure that the modifications took effect.
    $this->assertRaw(t('The %label search page has been updated.', ['%label' => 'Dummy search type']));
    $this->drupalGet('admin/config/search/pages/manage/dummy_search_type');
    $this->assertTrue($this->xpath('//select[@id="edit-extra-type-settings-boost"]//option[@value="ii" and @selected="selected"]'), 'Module specific settings can be changed');
  }

  /**
   * Verifies that you can disable individual search plugins.
   */
  public function testSearchModuleDisabling() {
    // Array of search plugins to test: 'keys' are the keywords to search for,
    // and 'text' is the text to assert is on the results page.
    $plugin_info = [
      'node_search' => [
        'keys' => 'pizza',
        'text' => $this->searchNode->label(),
      ],
      'user_search' => [
        'keys' => $this->searchUser->getAccountName(),
        'text' => $this->searchUser->getEmail(),
      ],
      'dummy_search_type' => [
        'keys' => 'foo',
        'text' => 'Dummy search snippet to display',
      ],
    ];
    $plugins = array_keys($plugin_info);
    /** @var $entities \Drupal\search\SearchPageInterface[] */
    $entities = SearchPage::loadMultiple();
    // Disable all of the search pages.
    foreach ($entities as $entity) {
      $entity->disable()->save();
    }

    // Test each plugin if it's enabled as the only search plugin.
    foreach ($entities as $entity_id => $entity) {
      $this->setDefaultThroughUi($entity_id);

      // Run a search from the correct search URL.
      $info = $plugin_info[$entity_id];
      $this->drupalGet('search/' . $entity->getPath(), ['query' => ['keys' => $info['keys']]]);
      $this->assertResponse(200);
      $this->assertNoText('no results', $entity->label() . ' search found results');
      $this->assertText($info['text'], 'Correct search text found');

      // Verify that other plugin search tab labels are not visible.
      foreach ($plugins as $other) {
        if ($other != $entity_id) {
          $label = $entities[$other]->label();
          $this->assertNoText($label, $label . ' search tab is not shown');
        }
      }

      // Run a search from the search block on the node page. Verify you get
      // to this plugin's search results page.
      $terms = ['keys' => $info['keys']];
      $this->drupalPostForm('node', $terms, t('Search'));
      $current = $this->getURL();
      $expected = Url::fromRoute('search.view_' . $entity->id(), [], ['query' => ['keys' => $info['keys']], 'absolute' => TRUE])->toString();
      $this->assertEqual($current, $expected, 'Block redirected to right search page');

      // Try an invalid search path, which should 404.
      $this->drupalGet('search/not_a_plugin_path');
      $this->assertResponse(404);

      $entity->disable()->save();
    }

    // Set the node search as default.
    $this->setDefaultThroughUi('node_search');

    // Test with all search plugins enabled. When you go to the search
    // page or run search, all plugins should be shown.
    foreach ($entities as $entity) {
      $entity->enable()->save();
    }

    \Drupal::service('router.builder')->rebuild();

    $paths = [
      ['path' => 'search/node', 'options' => ['query' => ['keys' => 'pizza']]],
      ['path' => 'search/node', 'options' => []],
    ];

    foreach ($paths as $item) {
      $this->drupalGet($item['path'], $item['options']);
      foreach ($plugins as $entity_id) {
        $label = $entities[$entity_id]->label();
        $this->assertText($label, new FormattableMarkup('%label search tab is shown', ['%label' => $label]));
      }
    }
  }

  /**
   * Tests the ordering of search pages on a clean install.
   */
  public function testDefaultSearchPageOrdering() {
    $this->drupalGet('search');
    $elements = $this->xpath('//*[contains(@class, :class)]//a', [':class' => 'tabs primary']);
    $this->assertIdentical($elements[0]->getAttribute('href'), Url::fromRoute('search.view_node_search')->toString());
    $this->assertIdentical($elements[1]->getAttribute('href'), Url::fromRoute('search.view_dummy_search_type')->toString());
    $this->assertIdentical($elements[2]->getAttribute('href'), Url::fromRoute('search.view_user_search')->toString());
  }

  /**
   * Tests multiple search pages of the same type.
   */
  public function testMultipleSearchPages() {
    $this->assertDefaultSearch('node_search', 'The default page is set to the installer default.');
    $search_storage = \Drupal::entityTypeManager()->getStorage('search_page');
    $entities = $search_storage->loadMultiple();
    $search_storage->delete($entities);
    $this->assertDefaultSearch(FALSE);

    // Ensure that no search pages are configured.
    $this->drupalGet('admin/config/search/pages');
    $this->assertText(t('No search pages have been configured.'));

    // Add a search page.
    $edit = [];
    $edit['search_type'] = 'search_extra_type_search';
    $this->drupalPostForm(NULL, $edit, t('Add search page'));
    $this->assertTitle('Add new search page | Drupal');

    $first = [];
    $first['label'] = $this->randomString();
    $first_id = $first['id'] = strtolower($this->randomMachineName(8));
    $first['path'] = strtolower($this->randomMachineName(8));
    $this->drupalPostForm(NULL, $first, t('Save'));
    $this->assertDefaultSearch($first_id, 'The default page matches the only search page.');
    $this->assertRaw(t('The %label search page has been added.', ['%label' => $first['label']]));

    // Attempt to add a search page with an existing path.
    $edit = [];
    $edit['search_type'] = 'search_extra_type_search';
    $this->drupalPostForm(NULL, $edit, t('Add search page'));
    $edit = [];
    $edit['label'] = $this->randomString();
    $edit['id'] = strtolower($this->randomMachineName(8));
    $edit['path'] = $first['path'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The search page path must be unique.'));

    // Add a second search page.
    $second = [];
    $second['label'] = $this->randomString();
    $second_id = $second['id'] = strtolower($this->randomMachineName(8));
    $second['path'] = strtolower($this->randomMachineName(8));
    $this->drupalPostForm(NULL, $second, t('Save'));
    $this->assertDefaultSearch($first_id, 'The default page matches the only search page.');

    // Ensure both search pages have their tabs displayed.
    $this->drupalGet('search');
    $elements = $this->xpath('//*[contains(@class, :class)]//a', [':class' => 'tabs primary']);
    $this->assertIdentical($elements[0]->getAttribute('href'), Url::fromRoute('search.view_' . $first_id)->toString());
    $this->assertIdentical($elements[1]->getAttribute('href'), Url::fromRoute('search.view_' . $second_id)->toString());

    // Switch the weight of the search pages and check the order of the tabs.
    $edit = [
      'entities[' . $first_id . '][weight]' => 10,
      'entities[' . $second_id . '][weight]' => -10,
    ];
    $this->drupalPostForm('admin/config/search/pages', $edit, t('Save configuration'));
    $this->drupalGet('search');
    $elements = $this->xpath('//*[contains(@class, :class)]//a', [':class' => 'tabs primary']);
    $this->assertIdentical($elements[0]->getAttribute('href'), Url::fromRoute('search.view_' . $second_id)->toString());
    $this->assertIdentical($elements[1]->getAttribute('href'), Url::fromRoute('search.view_' . $first_id)->toString());

    // Check the initial state of the search pages.
    $this->drupalGet('admin/config/search/pages');
    $this->verifySearchPageOperations($first_id, TRUE, FALSE, FALSE, FALSE);
    $this->verifySearchPageOperations($second_id, TRUE, TRUE, TRUE, FALSE);

    // Change the default search page.
    $this->clickLink(t('Set as default'));
    $this->assertRaw(t('The default search page is now %label. Be sure to check the ordering of your search pages.', ['%label' => $second['label']]));
    $this->verifySearchPageOperations($first_id, TRUE, TRUE, TRUE, FALSE);
    $this->verifySearchPageOperations($second_id, TRUE, FALSE, FALSE, FALSE);

    // Disable the first search page.
    $this->clickLink(t('Disable'));
    $this->assertResponse(200);
    $this->assertNoLink(t('Disable'));
    $this->verifySearchPageOperations($first_id, TRUE, TRUE, FALSE, TRUE);
    $this->verifySearchPageOperations($second_id, TRUE, FALSE, FALSE, FALSE);

    // Enable the first search page.
    $this->clickLink(t('Enable'));
    $this->assertResponse(200);
    $this->verifySearchPageOperations($first_id, TRUE, TRUE, TRUE, FALSE);
    $this->verifySearchPageOperations($second_id, TRUE, FALSE, FALSE, FALSE);

    // Test deleting.
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the search page %label?', ['%label' => $first['label']]));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertRaw(t('The search page %label has been deleted.', ['%label' => $first['label']]));
    $this->verifySearchPageOperations($first_id, FALSE, FALSE, FALSE, FALSE);
  }

  /**
   * Tests that the enable/disable/default routes are protected from CSRF.
   */
  public function testRouteProtection() {
    // Ensure that the enable and disable routes are protected.
    $this->drupalGet('admin/config/search/pages/manage/node_search/enable');
    $this->assertResponse(403);
    $this->drupalGet('admin/config/search/pages/manage/node_search/disable');
    $this->assertResponse(403);
    $this->drupalGet('admin/config/search/pages/manage/node_search/set-default');
    $this->assertResponse(403);
  }

  /**
   * Checks that the search page operations match expectations.
   *
   * @param string $id
   *   The search page ID to check.
   * @param bool $edit
   *   Whether the edit link is expected.
   * @param bool $delete
   *   Whether the delete link is expected.
   * @param bool $disable
   *   Whether the disable link is expected.
   * @param bool $enable
   *   Whether the enable link is expected.
   */
  protected function verifySearchPageOperations($id, $edit, $delete, $disable, $enable) {
    if ($edit) {
      $this->assertLinkByHref("admin/config/search/pages/manage/$id");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/pages/manage/$id");
    }
    if ($delete) {
      $this->assertLinkByHref("admin/config/search/pages/manage/$id/delete");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/pages/manage/$id/delete");
    }
    if ($disable) {
      $this->assertLinkByHref("admin/config/search/pages/manage/$id/disable");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/pages/manage/$id/disable");
    }
    if ($enable) {
      $this->assertLinkByHref("admin/config/search/pages/manage/$id/enable");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/pages/manage/$id/enable");
    }
  }

  /**
   * Checks that the default search page matches expectations.
   *
   * @param string $expected
   *   The expected search page.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in.
   */
  protected function assertDefaultSearch($expected, $message = '', $group = 'Other') {
    /** @var $search_page_repository \Drupal\search\SearchPageRepositoryInterface */
    $search_page_repository = \Drupal::service('search.search_page_repository');
    $this->assertIdentical($search_page_repository->getDefaultSearchPage(), $expected, $message, $group);
  }

  /**
   * Sets a search page as the default in the UI.
   *
   * @param string $entity_id
   *   The search page entity ID to enable.
   */
  protected function setDefaultThroughUi($entity_id) {
    $this->drupalGet('admin/config/search/pages');
    preg_match('|href="([^"]+' . $entity_id . '/set-default[^"]+)"|', $this->getSession()->getPage()->getContent(), $matches);

    $this->drupalGet($this->getAbsoluteUrl($matches[1]));
  }

}
