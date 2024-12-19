<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

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
    'field_ui',
    'file',
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
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span");

    $this->verifyDynamicPageCache($this->node->toUrl(), 'MISS');
    $this->verifyDynamicPageCache($this->node->toUrl(), 'HIT');
    // Top Bar is not visible if the feature flag module is disabled.
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span");
    $this->assertSession()->elementExists('xpath', '//div[@id="block-tabs"]');

    \Drupal::service('module_installer')->install(['navigation_top_bar']);

    // Test page does not include the Top Bar.
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span");

    // Top Bar is visible once the feature flag module is enabled.
    $this->verifyDynamicPageCache($this->node->toUrl(), 'MISS');
    $this->verifyDynamicPageCache($this->node->toUrl(), 'HIT');
    $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span");
    $this->assertSession()->elementTextEquals('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span", 'More actions');
    $this->assertSession()->elementNotExists('xpath', '//div[@id="block-tabs"]');

    // Find all the dropdown links and check if the top bar is there as well.
    $toolbar_links = $this->mink->getSession()->getPage()->find('xpath', '//*[@id="admin-local-tasks"]/ul');

    foreach ($toolbar_links->findAll('css', 'li') as $toolbar_link) {
      $this->clickLink($toolbar_link->getText());
      $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span");
      $this->assertSession()->elementTextEquals('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span", 'More actions');
      $this->assertSession()->elementNotExists('xpath', '//div[@id="block-tabs"]');
    }

    // Regular tabs are visible for user that cannot access to navigation.
    $this->drupalLogin($this->drupalCreateUser([
      'bypass node access',
    ]));

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/button/span");
    $this->assertSession()->elementExists('xpath', '//div[@id="block-tabs"]');
  }

}
