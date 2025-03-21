<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Url;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;

/**
 * Tests the top bar functionality.
 *
 * @group navigation
 */
class NavigationTopBarTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation',
    'node',
    'layout_builder',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An admin user to configure the test environment.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Node used to check top bar options.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'access navigation',
      'bypass node access',
      'configure any layout',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create a new content type and enable Layout Builder for it.
    $node_type = $this->createContentType(['type' => 'node_type']);
    LayoutBuilderEntityViewDisplay::load('node.node_type.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    // Place the tabs block to check its presence.
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'tabs']);

    // Enable some test blocks.
    $this->node = $this->drupalCreateNode(['type' => $node_type->id()]);
  }

  /**
   * Tests the top bar visibility.
   */
  public function testTopBarVisibility(): void {
    // Test page does not include the Top Bar.
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button");

    // Top Bar is visible on node pages.
    $this->verifyDynamicPageCache($this->node->toUrl(), 'MISS');
    $this->verifyDynamicPageCache($this->node->toUrl(), 'HIT');
    $this->assertSession()->elementExists('xpath', "(//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button)[1]");
    $this->assertSession()->elementTextEquals('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/a[contains(@class, 'toolbar-button--icon--pencil')]", "Edit");
    $this->assertSession()->elementAttributeContains('xpath', "(//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button)[1]", 'class', 'toolbar-button--icon--dots');

    // Find all the dropdown links and check if the top bar is there as well.
    $toolbar_links = $this->mink->getSession()->getPage()->find('xpath', '//*[@id="top-bar-page-actions"]/ul');

    foreach ($toolbar_links->findAll('css', 'li') as $toolbar_link) {
      $this->clickLink($toolbar_link->getText());
      $this->assertSession()->elementExists('xpath', "(//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button)[1]");
      $this->assertSession()->elementAttributeContains('xpath', "(//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button)[1]", 'class', 'toolbar-button--icon--dots');
      // Ensure that link to current page is not included in the dropdown.
      $url = $this->getSession()->getCurrentUrl();
      $this->assertSession()->linkByHrefNotExistsExact(parse_url($url, PHP_URL_PATH));
      // Ensure that the actions are displayed in the correct order.
      $this->assertActionsWeight($toolbar_links);
    }

    // Regular tabs are visible for user that cannot access to navigation.
    $this->drupalLogin($this->drupalCreateUser([
      'bypass node access',
    ]));

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button");
    $this->assertSession()->elementExists('xpath', '//div[@id="block-tabs"]');
  }

  /**
   * Asserts that top bar actions respect local tasks weights.
   *
   * @param \Behat\Mink\Element\NodeElement $toolbar_links
   *   Action links to assert.
   */
  protected function assertActionsWeight(NodeElement $toolbar_links): void {
    // Displayed action links in the top bar.
    $displayed_links = array_map(
      fn($link) => $link->getText(),
      $toolbar_links->findAll('css', 'li')
    );

    // Extract the route name from the URL.
    $current_url = $this->getSession()->getCurrentUrl();
    // Convert alias to system path.
    $path = parse_url($current_url, PHP_URL_PATH);

    if ($GLOBALS['base_path'] !== '/') {
      $path = str_replace($GLOBALS['base_path'], '/', $path);
    }

    // Get local tasks for the current route.
    $entity_local_tasks = \Drupal::service('plugin.manager.menu.local_task')->getLocalTasks(Url::fromUserInput($path)->getRouteName());

    // Sort order of tabs based on their weights.
    uasort($entity_local_tasks['tabs'], [SortArray::class, 'sortByWeightProperty']);

    // Extract the expected order based on sorted weights.
    $expected_order = array_values(array_map(fn($task) => $task['#link']['title'], $entity_local_tasks['tabs']));

    // Filter out elements not in displayed_links.
    $expected_order = array_values(array_filter($expected_order, fn($title) => in_array($title, $displayed_links, TRUE)));

    // Ensure that the displayed links match the expected order.
    $this->assertSame($expected_order, $displayed_links, 'Local tasks are displayed in the correct order based on their weights.');
  }

}
