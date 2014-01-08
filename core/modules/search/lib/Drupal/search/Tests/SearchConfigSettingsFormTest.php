<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchConfigSettingsFormTest.
 */

namespace Drupal\search\Tests;

/**
 * Test config page.
 */
class SearchConfigSettingsFormTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'search_extra_type');

  /**
   * User who can search and administer search.
   *
   * @var \Drupal\user\UserInterface
   */
  public $search_user;

  /**
   * Node indexed for searching.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $search_node;

  public static function getInfo() {
    return array(
      'name' => 'Config settings form',
      'description' => 'Verify the search config settings form.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Login as a user that can create and search content.
    $this->search_user = $this->drupalCreateUser(array('search content', 'administer search', 'administer nodes', 'bypass node access', 'access user profiles', 'administer users', 'administer blocks'));
    $this->drupalLogin($this->search_user);

    // Add a single piece of content and index it.
    $node = $this->drupalCreateNode();
    $this->search_node = $node;
    // Link the node to itself to test that it's only indexed once. The content
    // also needs the word "pizza" so we can use it as the search keyword.
    $body_key = 'body[0][value]';
    $edit[$body_key] = l($node->label(), 'node/' . $node->id()) . ' pizza sandwich';
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Enable the search block.
    $this->drupalPlaceBlock('search_form_block');
  }

  /**
   * Verifies the search settings form.
   */
  function testSearchSettingsPage() {

    // Test that the settings form displays the correct count of items left to index.
    $this->drupalGet('admin/config/search/settings');
    $this->assertText(t('There are @count items left to index.', array('@count' => 0)));

    // Test the re-index button.
    $this->drupalPostForm('admin/config/search/settings', array(), t('Re-index site'));
    $this->assertText(t('Are you sure you want to re-index the site'));
    $this->drupalPostForm('admin/config/search/settings/reindex', array(), t('Re-index site'));
    $this->assertText(t('The index will be rebuilt'));
    $this->drupalGet('admin/config/search/settings');
    $this->assertText(t('There is 1 item left to index.'));

    // Test that the form saves with the default values.
    $this->drupalPostForm('admin/config/search/settings', array(), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Form saves with the default values.');

    // Test that the form does not save with an invalid word length.
    $edit = array(
      'minimum_word_size' => $this->randomName(3),
    );
    $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));
    $this->assertNoText(t('The configuration options have been saved.'), 'Form does not save with an invalid word length.');
  }

  /**
   * Verifies plugin-supplied settings form.
   */
  function testSearchModuleSettingsPage() {
    $this->drupalGet('admin/config/search/settings');
    $this->clickLink(t('Edit'), 1);

    // Ensure that the default setting was picked up from the default config
    $this->assertTrue($this->xpath('//select[@id="edit-extra-type-settings-boost"]//option[@value="bi" and @selected="selected"]'), 'Module specific settings are picked up from the default config');

    // Change extra type setting and also modify a common search setting.
    $edit = array(
      'extra_type_settings[boost]' => 'ii',
    );
    $this->drupalPostForm(NULL, $edit, t('Save search page'));

    // Ensure that the modifications took effect.
    $this->assertRaw(t('The %label search page has been updated.', array('%label' => 'Dummy search type')));
    $this->drupalGet('admin/config/search/settings/manage/dummy_search_type');
    $this->assertTrue($this->xpath('//select[@id="edit-extra-type-settings-boost"]//option[@value="ii" and @selected="selected"]'), 'Module specific settings can be changed');
  }

  /**
   * Verifies that you can disable individual search plugins.
   */
  function testSearchModuleDisabling() {
    // Array of search plugins to test: 'keys' are the keywords to search for,
    // and 'text' is the text to assert is on the results page.
    $plugin_info = array(
      'node_search' => array(
        'keys' => 'pizza',
        'text' => $this->search_node->label(),
      ),
      'user_search' => array(
        'keys' => $this->search_user->getUsername(),
        'text' => $this->search_user->getEmail(),
      ),
      'dummy_search_type' => array(
        'keys' => 'foo',
        'text' => 'Dummy search snippet to display',
      ),
    );
    $plugins = array_keys($plugin_info);
    /** @var $entities \Drupal\search\SearchPageInterface[] */
    $entities = entity_load_multiple('search_page');
    // Disable all of the search pages.
    foreach ($entities as $entity) {
      $entity->disable()->save();
    }

    // Test each plugin if it's enabled as the only search plugin.
    foreach ($entities as $entity_id => $entity) {
      // Set this as default.
      $this->drupalGet("admin/config/search/settings/manage/$entity_id/set-default");

      // Run a search from the correct search URL.
      $info = $plugin_info[$entity_id];
      $this->drupalGet('search/' . $entity->getPath() . '/' . $info['keys']);
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
      $terms = array('search_block_form' => $info['keys']);
      $this->drupalPostForm('node', $terms, t('Search'));
      $this->assertEqual(
        $this->getURL(),
        \Drupal::url('search.view_' . $entity->id(), array('keys' => $info['keys']), array('absolute' => TRUE)),
        'Block redirected to right search page');

      // Try an invalid search path, which should 404.
      $this->drupalGet('search/not_a_plugin_path');
      $this->assertResponse(404);

      $entity->disable()->save();
    }

    // Test with all search plugins enabled. When you go to the search
    // page or run search, all plugins should be shown.
    foreach ($entities as $entity) {
      $entity->enable()->save();
    }
    // Set the node search as default.
    $this->drupalGet('admin/config/search/settings/manage/node_search/set-default');

    foreach (array('search/node/pizza', 'search/node') as $path) {
      $this->drupalGet($path);
      foreach ($plugins as $entity_id) {
        $label = $entities[$entity_id]->label();
        $this->assertText($label, format_string('%label search tab is shown', array('%label' => $label)));
      }
    }
  }

  /**
   * Tests the ordering of search pages on a clean install.
   */
  public function testDefaultSearchPageOrdering() {
    $this->drupalGet('search');
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(':class' => 'tabs primary'));
    $this->assertIdentical((string) $elements[0]['href'], url('search/node'));
    $this->assertIdentical((string) $elements[1]['href'], url('search/user'));
  }

  /**
   * Tests multiple search pages of the same type.
   */
  public function testMultipleSearchPages() {
    $this->assertDefaultSearch('node_search', 'The default page is set to the installer default.');
    $search_storage = \Drupal::entityManager()->getStorageController('search_page');
    $entities = $search_storage->loadMultiple();
    $search_storage->delete($entities);
    $this->assertDefaultSearch(FALSE);

    // Ensure that no search pages are configured.
    $this->drupalGet('admin/config/search/settings');
    $this->assertText(t('No search pages have been configured.'));

    // Add a search page.
    $edit = array();
    $edit['search_type'] = 'search_extra_type_search';
    $this->drupalPostForm(NULL, $edit, t('Add new page'));
    $this->assertTitle('Add new search page | Drupal');

    $first = array();
    $first['label'] = $this->randomString();
    $first_id = $first['id'] = strtolower($this->randomName(8));
    $first['path'] = strtolower($this->randomName(8));
    $this->drupalPostForm(NULL, $first, t('Add search page'));
    $this->assertDefaultSearch($first_id, 'The default page matches the only search page.');
    $this->assertRaw(t('The %label search page has been added.', array('%label' => $first['label'])));

    // Attempt to add a search page with an existing path.
    $edit = array();
    $edit['search_type'] = 'search_extra_type_search';
    $this->drupalPostForm(NULL, $edit, t('Add new page'));
    $edit = array();
    $edit['label'] = $this->randomString();
    $edit['id'] = strtolower($this->randomName(8));
    $edit['path'] = $first['path'];
    $this->drupalPostForm(NULL, $edit, t('Add search page'));
    $this->assertText(t('The search page path must be unique.'));

    // Add a second search page.
    $second = array();
    $second['label'] = $this->randomString();
    $second_id = $second['id'] = strtolower($this->randomName(8));
    $second['path'] = strtolower($this->randomName(8));
    $this->drupalPostForm(NULL, $second, t('Add search page'));
    $this->assertDefaultSearch($first_id, 'The default page matches the only search page.');

    // Ensure both search pages have their tabs displayed.
    $this->drupalGet('search');
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(':class' => 'tabs primary'));
    $this->assertIdentical((string) $elements[0]['href'], url('search/' . $first['path']));
    $this->assertIdentical((string) $elements[1]['href'], url('search/' . $second['path']));

    // Switch the weight of the search pages and check the order of the tabs.
    $edit = array(
      'entities[' . $first_id . '][weight]' => 10,
      'entities[' . $second_id . '][weight]' => -10,
    );
    $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));
    $this->drupalGet('search');
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(':class' => 'tabs primary'));
    $this->assertIdentical((string) $elements[0]['href'], url('search/' . $second['path']));
    $this->assertIdentical((string) $elements[1]['href'], url('search/' . $first['path']));

    // Check the initial state of the search pages.
    $this->drupalGet('admin/config/search/settings');
    $this->verifySearchPageOperations($first_id, TRUE, FALSE, FALSE, FALSE);
    $this->verifySearchPageOperations($second_id, TRUE, TRUE, TRUE, FALSE);

    // Change the default search page.
    $this->clickLink(t('Set as default'));
    $this->assertRaw(t('The default search page is now %label. Be sure to check the ordering of your search pages.', array('%label' => $second['label'])));
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
    $this->assertRaw(t('Are you sure you want to delete the %label search page?', array('%label' => $first['label'])));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The %label search page has been deleted.', array('%label' => $first['label'])));
    $this->verifySearchPageOperations($first_id, FALSE, FALSE, FALSE, FALSE);
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
      $this->assertLinkByHref("admin/config/search/settings/manage/$id");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/settings/manage/$id");
    }
    if ($delete) {
      $this->assertLinkByHref("admin/config/search/settings/manage/$id/delete");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/settings/manage/$id/delete");
    }
    if ($disable) {
      $this->assertLinkByHref("admin/config/search/settings/manage/$id/disable");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/settings/manage/$id/disable");
    }
    if ($enable) {
      $this->assertLinkByHref("admin/config/search/settings/manage/$id/enable");
    }
    else {
      $this->assertNoLinkByHref("admin/config/search/settings/manage/$id/enable");
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

}
