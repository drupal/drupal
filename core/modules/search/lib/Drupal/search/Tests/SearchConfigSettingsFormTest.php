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

  public $search_user;
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

    // Test that the settings form displays the correct count of items left to index.
    $this->drupalGet('admin/config/search/settings');

    // Ensure that the settings fieldset for the test plugin is not present on
    // the page
    $this->assertNoText(t('Extra type settings'));
    $this->assertNoText(t('Boost method'));

    // Ensure that the test plugin is listed as an option
    $this->assertTrue($this->xpath('//input[@id="edit-active-plugins-search-extra-type-search"]'), 'Checkbox for activating search for an extra plugin is visible');
    $this->assertTrue($this->xpath('//input[@id="edit-default-plugin-search-extra-type-search"]'), 'Radio button for setting extra plugin as default search plugin is visible');

    // Enable search for the test plugin
    $edit['active_plugins[search_extra_type_search]'] = 'search_extra_type_search';
    $edit['default_plugin'] = 'search_extra_type_search';
    $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));

    // Ensure that the settings fieldset is visible after enabling search for
    // the test plugin
    $this->assertText(t('Extra type settings'));
    $this->assertText(t('Boost method'));

    // Ensure that the default setting was picked up from the default config
    $this->assertTrue($this->xpath('//select[@id="edit-extra-type-settings-boost"]//option[@value="bi" and @selected="selected"]'), 'Module specific settings are picked up from the default config');

    // Change extra type setting and also modify a common search setting.
    $edit = array(
      'extra_type_settings[boost]' => 'ii',
      'minimum_word_size' => 5,
    );
    $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));

    // Ensure that the modifications took effect.
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertTrue($this->xpath('//select[@id="edit-extra-type-settings-boost"]//option[@value="ii" and @selected="selected"]'), 'Module specific settings can be changed');
    $this->assertTrue($this->xpath('//input[@id="edit-minimum-word-size" and @value="5"]'), 'Common search settings can be modified if a plugin-specific form is active');
  }

  /**
   * Verifies that you can disable individual search plugins.
   */
  function testSearchModuleDisabling() {
    // Array of search plugins to test: 'path' is the search path, 'title' is
    // the tab title, 'keys' are the keywords to search for, and 'text' is
    // the text to assert is on the results page.
    $plugin_info = array(
      'node_search' => array(
        'path' => 'node',
        'title' => 'Content',
        'keys' => 'pizza',
        'text' => $this->search_node->label(),
      ),
      'user_search' => array(
        'path' => 'user',
        'title' => 'User',
        'keys' => $this->search_user->getUsername(),
        'text' => $this->search_user->getEmail(),
      ),
      'search_extra_type_search' => array(
        'path' => 'dummy_path',
        'title' => 'Dummy search type',
        'keys' => 'foo',
        'text' => 'Dummy search snippet to display',
      ),
    );
    $plugins = array_keys($plugin_info);

    // Test each plugin if it's enabled as the only search plugin.
    foreach ($plugins as $plugin) {
      // Enable the one plugin and disable other ones.
      $info = $plugin_info[$plugin];
      $edit = array();
      foreach ($plugins as $other) {
        $edit['active_plugins[' . $other . ']'] = (($other == $plugin) ? $plugin : FALSE);
      }
      $edit['default_plugin'] = $plugin;
      $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));

      // Run a search from the correct search URL.
      $this->drupalGet('search/' . $info['path'] . '/' . $info['keys']);
      $this->assertNoText('no results', $info['title'] . ' search found results');
      $this->assertText($info['text'], 'Correct search text found');

      // Verify that other plugin search tab titles are not visible.
      foreach ($plugins as $other) {
        if ($other != $plugin) {
          $title = $plugin_info[$other]['title'];
          $this->assertNoText($title, $title . ' search tab is not shown');
        }
      }

      // Run a search from the search block on the node page. Verify you get
      // to this plugin's search results page.
      $terms = array('search_block_form' => $info['keys']);
      $this->drupalPostForm('node', $terms, t('Search'));
      $this->assertEqual(
        $this->getURL(),
        url('search/' . $info['path'] . '/' . $info['keys'], array('absolute' => TRUE)),
        'Block redirected to right search page');

      // Try an invalid search path. Should redirect to our active plugin.
      $this->drupalGet('search/not_a_plugin_path');
      $this->assertEqual(
        $this->getURL(),
        url('search/' . $info['path'], array('absolute' => TRUE)),
        'Invalid search path redirected to default search page');
    }

    // Test with all search plugins enabled. When you go to the search
    // page or run search, all plugins should be shown.
    $edit = array();
    foreach ($plugins as $plugin) {
      $edit['active_plugins[' . $plugin . ']'] = $plugin;
    }
    $edit['default_plugin'] = 'node_search';

    $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));

    foreach (array('search/node/pizza', 'search/node') as $path) {
      $this->drupalGet($path);
      foreach ($plugins as $plugin) {
        $title = $plugin_info[$plugin]['title'];
        $this->assertText($title, format_string('%title search tab is shown', array('%title' => $title)));
      }
    }
  }
}
