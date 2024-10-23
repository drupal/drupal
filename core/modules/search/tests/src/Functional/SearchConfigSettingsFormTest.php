<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional;

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
  protected static $modules = [
    'block',
    'dblog',
    'node',
    'search',
    'search_extra_type',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Log in as a user that can create and search content.
    $this->searchUser = $this->drupalCreateUser([
      'search content',
      'administer search',
      'administer nodes',
      'bypass node access',
      'access user profiles',
      'administer users',
      'administer blocks',
      'access site reports',
    ]);
    $this->drupalLogin($this->searchUser);

    // Add a single piece of content and index it.
    $node = $this->drupalCreateNode();
    $this->searchNode = $node;
    // Link the node to itself to test that it's only indexed once. The content
    // also needs the word "pizza" so we can use it as the search keyword.
    $body_key = 'body[0][value]';
    $edit[$body_key] = Link::fromTextAndUrl($node->label(), $node->toUrl())->toString() . ' pizza sandwich';
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Enable the search block.
    $this->drupalPlaceBlock('search_form_block');
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'local_tasks']);
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Verifies the search settings form.
   */
  public function testSearchSettingsPage(): void {

    // Test that the settings form displays the correct count of items left to index.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There are 0 items left to index.');

    // Test the re-index button.
    $this->drupalGet('admin/config/search/pages');
    $this->submitForm([], 'Re-index site');
    $this->assertSession()->pageTextContains('Are you sure you want to re-index the site');
    $this->drupalGet('admin/config/search/pages/reindex');
    $this->submitForm([], 'Re-index site');
    $this->assertSession()->statusMessageContains('All search indexes will be rebuilt', 'status');
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There is 1 item left to index.');

    // Test that the form saves with the default values.
    $this->drupalGet('admin/config/search/pages');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->statusMessageContains('The configuration options have been saved.', 'status');

    // Test that the form does not save with an invalid word length.
    $edit = [
      'minimum_word_size' => $this->randomMachineName(3),
    ];
    $this->drupalGet('admin/config/search/pages');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageNotContains('The configuration options have been saved.');
    $this->assertSession()->statusMessageContains('Minimum word length to index must be a number.', 'error');

    // Test logging setting. It should be off by default.
    $text = $this->randomMachineName(5);
    $this->drupalGet('search/node');
    $this->submitForm(['keys' => $text], 'Search');
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->linkNotExists('Searched Content for ' . $text . '.', 'Search was not logged');

    // Turn on logging.
    $edit = ['logging' => TRUE];
    $this->drupalGet('admin/config/search/pages');
    $this->submitForm($edit, 'Save configuration');
    $text = $this->randomMachineName(5);
    $this->drupalGet('search/node');
    $this->submitForm(['keys' => $text], 'Search');
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->linkExists('Searched Content for ' . $text . '.', 0, 'Search was logged');

  }

  /**
   * Verifies plugin-supplied settings form.
   */
  public function testSearchModuleSettingsPage(): void {
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit', 1);

    // Ensure that the default setting was picked up from the default config
    $this->assertTrue($this->assertSession()->optionExists('edit-extra-type-settings-boost', 'bi')->isSelected());

    // Change extra type setting and also modify a common search setting.
    $edit = [
      'extra_type_settings[boost]' => 'ii',
    ];
    $this->submitForm($edit, 'Save search page');

    // Ensure that the modifications took effect.
    $this->assertSession()->statusMessageContains("The Dummy search type search page has been updated.", 'status');
    $this->drupalGet('admin/config/search/pages/manage/dummy_search_type');
    $this->assertTrue($this->assertSession()->optionExists('edit-extra-type-settings-boost', 'ii')->isSelected());
  }

  /**
   * Verifies that you can disable individual search plugins.
   */
  public function testSearchModuleDisabling(): void {
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
    /** @var \Drupal\search\SearchPageInterface[] $entities */
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
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextNotContains('no results');
      $this->assertSession()->pageTextContains($info['text']);

      // Verify that other plugin search tab labels are not visible.
      foreach ($plugins as $other) {
        if ($other != $entity_id) {
          $path = 'search/' . $entities[$other]->getPath();
          $this->assertSession()->elementNotExists('xpath', '//div[@id="block-local-tasks"]//li/a[@data-drupal-link-system-path="' . $path . '"]');
        }
      }

      // Run a search from the search block on the node page. Verify you get
      // to this plugin's search results page.
      $terms = ['keys' => $info['keys']];
      $this->drupalGet('node');
      $this->submitForm($terms, 'Search');
      $current = $this->getURL();
      $expected = Url::fromRoute('search.view_' . $entity->id(), [], ['query' => ['keys' => $info['keys']], 'absolute' => TRUE])->toString();
      $this->assertEquals($expected, $current, 'Block redirected to right search page');

      // Try an invalid search path, which should 404.
      $this->drupalGet('search/not_a_plugin_path');
      $this->assertSession()->statusCodeEquals(404);

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
        $path = 'search/' . $entities[$entity_id]->getPath();
        $label = $entities[$entity_id]->label();
        $this->assertSession()->elementTextContains('xpath', '//div[@id="block-local-tasks"]//li/a[@data-drupal-link-system-path="' . $path . '"]', $label);
      }
    }
  }

  /**
   * Tests the ordering of search pages on a clean install.
   */
  public function testDefaultSearchPageOrdering(): void {
    $this->drupalGet('search');
    $elements = $this->xpath('//div[@id="block-local-tasks"]//a');
    $this->assertSame(Url::fromRoute('search.view_node_search')->toString(), $elements[0]->getAttribute('href'));
    $this->assertSame(Url::fromRoute('search.view_dummy_search_type')->toString(), $elements[1]->getAttribute('href'));
    $this->assertSame(Url::fromRoute('search.view_user_search')->toString(), $elements[2]->getAttribute('href'));
  }

  /**
   * Tests multiple search pages of the same type.
   */
  public function testMultipleSearchPages(): void {
    $this->assertDefaultSearch('node_search', 'The default page is set to the installer default.');
    $search_storage = \Drupal::entityTypeManager()->getStorage('search_page');
    $entities = $search_storage->loadMultiple();
    $search_storage->delete($entities);
    $this->assertDefaultSearch(FALSE);

    // Ensure that no search pages are configured.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('No search pages have been configured.');

    // Add a search page.
    $edit = [];
    $edit['search_type'] = 'search_extra_type_search';
    $this->submitForm($edit, 'Add search page');
    $this->assertSession()->titleEquals('Add new search page | Drupal');

    $first = [];
    $first['label'] = $this->randomString();
    $first_id = $first['id'] = $this->randomMachineName(8);
    $first['path'] = $this->randomMachineName(8);
    $this->submitForm($first, 'Save');
    $this->assertDefaultSearch($first_id, 'The default page matches the only search page.');
    $this->assertSession()->statusMessageContains("The {$first['label']} search page has been added.", 'status');

    // Attempt to add a search page with an existing path.
    $edit = [];
    $edit['search_type'] = 'search_extra_type_search';
    $this->submitForm($edit, 'Add search page');
    $edit = [];
    $edit['label'] = $this->randomString();
    $edit['id'] = $this->randomMachineName(8);
    $edit['path'] = $first['path'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusMessageContains('The search page path must be unique.', 'error');

    // Add a second search page.
    $second = [];
    $second['label'] = $this->randomString();
    $second_id = $second['id'] = $this->randomMachineName(8);
    $second['path'] = $this->randomMachineName(8);
    $this->submitForm($second, 'Save');
    $this->assertDefaultSearch($first_id, 'The default page matches the only search page.');

    // Ensure both search pages have their tabs displayed.
    $this->drupalGet('search');
    $elements = $this->xpath('//div[@id="block-local-tasks"]//a');
    $this->assertSame(Url::fromRoute('search.view_' . $first_id)->toString(), $elements[0]->getAttribute('href'));
    $this->assertSame(Url::fromRoute('search.view_' . $second_id)->toString(), $elements[1]->getAttribute('href'));

    // Switch the weight of the search pages and check the order of the tabs.
    $edit = [
      'entities[' . $first_id . '][weight]' => 10,
      'entities[' . $second_id . '][weight]' => -10,
    ];
    $this->drupalGet('admin/config/search/pages');
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('search');
    $elements = $this->xpath('//div[@id="block-local-tasks"]//a');
    $this->assertSame(Url::fromRoute('search.view_' . $second_id)->toString(), $elements[0]->getAttribute('href'));
    $this->assertSame(Url::fromRoute('search.view_' . $first_id)->toString(), $elements[1]->getAttribute('href'));

    // Check the initial state of the search pages.
    $this->drupalGet('admin/config/search/pages');
    $this->verifySearchPageOperations($first_id, TRUE, FALSE, FALSE, FALSE);
    $this->verifySearchPageOperations($second_id, TRUE, TRUE, TRUE, FALSE);

    // Change the default search page.
    $this->clickLink('Set as default');
    $this->assertSession()->statusMessageContains("The default search page is now {$second['label']}. Be sure to check the ordering of your search pages.", 'status');
    $this->verifySearchPageOperations($first_id, TRUE, TRUE, TRUE, FALSE);
    $this->verifySearchPageOperations($second_id, TRUE, FALSE, FALSE, FALSE);

    // Disable the first search page.
    $this->clickLink('Disable');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Disable');
    $this->verifySearchPageOperations($first_id, TRUE, TRUE, FALSE, TRUE);
    $this->verifySearchPageOperations($second_id, TRUE, FALSE, FALSE, FALSE);

    // Enable the first search page.
    $this->clickLink('Enable');
    $this->assertSession()->statusCodeEquals(200);
    $this->verifySearchPageOperations($first_id, TRUE, TRUE, TRUE, FALSE);
    $this->verifySearchPageOperations($second_id, TRUE, FALSE, FALSE, FALSE);

    // Test deleting.
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the search page {$first['label']}?");
    $this->submitForm([], 'Delete');
    $this->assertSession()->statusMessageContains("The search page {$first['label']} has been deleted.", 'status');
    $this->verifySearchPageOperations($first_id, FALSE, FALSE, FALSE, FALSE);
  }

  /**
   * Tests that the enable/disable/default routes are protected from CSRF.
   */
  public function testRouteProtection(): void {
    // Ensure that the enable and disable routes are protected.
    $this->drupalGet('admin/config/search/pages/manage/node_search/enable');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/search/pages/manage/node_search/disable');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/search/pages/manage/node_search/set-default');
    $this->assertSession()->statusCodeEquals(403);
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
  protected function verifySearchPageOperations($id, $edit, $delete, $disable, $enable): void {
    if ($edit) {
      $this->assertSession()->linkByHrefExists("admin/config/search/pages/manage/$id");
    }
    else {
      $this->assertSession()->linkByHrefNotExists("admin/config/search/pages/manage/$id");
    }
    if ($delete) {
      $this->assertSession()->linkByHrefExists("admin/config/search/pages/manage/$id/delete");
    }
    else {
      $this->assertSession()->linkByHrefNotExists("admin/config/search/pages/manage/$id/delete");
    }
    if ($disable) {
      $this->assertSession()->linkByHrefExists("admin/config/search/pages/manage/$id/disable");
    }
    else {
      $this->assertSession()->linkByHrefNotExists("admin/config/search/pages/manage/$id/disable");
    }
    if ($enable) {
      $this->assertSession()->linkByHrefExists("admin/config/search/pages/manage/$id/enable");
    }
    else {
      $this->assertSession()->linkByHrefNotExists("admin/config/search/pages/manage/$id/enable");
    }
  }

  /**
   * Checks that the default search page matches expectations.
   *
   * @param string|false $expected
   *   The expected search page.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @internal
   */
  protected function assertDefaultSearch($expected, string $message = ''): void {
    /** @var \Drupal\search\SearchPageRepositoryInterface $search_page_repository */
    $search_page_repository = \Drupal::service('search.search_page_repository');
    $this->assertSame($expected, $search_page_repository->getDefaultSearchPage(), $message);
  }

  /**
   * Sets a search page as the default in the UI.
   *
   * @param string $entity_id
   *   The search page entity ID to enable.
   */
  protected function setDefaultThroughUi($entity_id): void {
    $this->drupalGet('admin/config/search/pages');
    preg_match('|href="([^"]+' . $entity_id . '/set-default[^"]+)"|', $this->getSession()->getPage()->getContent(), $matches);

    $this->drupalGet($this->getAbsoluteUrl($matches[1]));
  }

}
