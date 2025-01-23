<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Url;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;

/**
 * Tests for \Drupal\navigation\Plugin\Block\NavigationLinkBlockTest.
 *
 * @group navigation
 */
class NavigationLinkBlockTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation', 'test_page_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with permission to administer navigation blocks and access navigation.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An authenticated user to test navigation block caching.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an admin user, log in and enable test navigation blocks.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'access navigation',
      'view test entity',
    ]);

    // Create additional users to test caching modes.
    $this->normalUser = $this->drupalCreateUser([
      'access navigation',
    ]);
  }

  /**
   * Test output of the link navigation with regards to caching and contents.
   */
  public function testNavigationLinkBlockCache(): void {
    $label = 'Admin Main Page';
    $link_title = 'Navigation Settings';
    $link_uri = '/admin/config/user-interface/navigation/settings';
    $link_icon = 'admin-link';
    $this->appendNavigationLinkBlock($label, $link_title, 'internal:' . $link_uri, $link_icon);
    // Verify some basic cacheability metadata. Ensures that we're not doing
    // anything so egregious as to upset expected caching behavior. In this
    // case, as an anonymous user, we should have zero effect on the page.
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->verifyPageCache($test_page_url, 'MISS');
    $this->verifyPageCache($test_page_url, 'HIT');

    // Login as a limited access user, and verify that the dynamic page cache
    // is working as expected.
    $this->drupalLogin($this->normalUser);
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    // We should not see the admin page link in the page.
    $link_selector = '.admin-toolbar__item .toolbar-button--icon--' . $link_icon;
    $this->assertSession()->elementNotExists('css', $link_selector);
    $this->assertSession()->pageTextNotContains($link_title);
    $this->assertSession()->pageTextNotContains($label);

    // Login as a different user, UI should update.
    $this->drupalLogin($this->adminUser);
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->drupalGet(Url::fromRoute('navigation.settings'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', $link_selector);
    $this->assertSession()->pageTextContains($link_title);
    $this->assertSession()->pageTextContains($label);
    $this->assertSession()
      ->elementTextContains('css', $link_selector, $link_title);
    // The link should link to the admin page.
    $link = $this->getSession()->getPage()->find('named', [
      'link',
      $link_title,
    ]);
    $this->assertStringContainsString('/admin/config/user-interface/navigation/settings', $link->getAttribute('href'));
  }

  /**
   * Test block visibility based on the link access logic.
   */
  public function testNavigationLinkBlockVisibility(): void {
    // Add a link to an external URL.
    $external_label = 'External Link Block';
    $external_link_title = 'Link to example';
    $this->appendNavigationLinkBlock($external_label, $external_link_title, 'http://example.com', 'external');
    // Create an entity and create a link to it.
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_test_storage = $entity_type_manager->getStorage('entity_test');
    $entity_test_link = $entity_test_storage->create(['name' => 'test']);
    $entity_test_link->save();
    $entity_label = 'Entity Link BLock';
    $entity_link_title = 'Link to entity';
    $this->appendNavigationLinkBlock($entity_label, $entity_link_title, 'entity:entity_test/' . $entity_test_link->id(), 'entity');
    // Link to admin page.
    $admin_label = 'Admin Main Page';
    $admin_link_title = 'Navigation Settings';
    $this->appendNavigationLinkBlock($admin_label, $admin_link_title, 'internal:/admin/config/user-interface/navigation/settings', 'admin');
    // Link to generic internal page (Help Link).
    $help_label = 'Help Block';
    $help_link_title = 'Link to help';
    $this->appendNavigationLinkBlock($help_label, $help_link_title, 'internal:/admin/help', 'internal');

    // Admin user should be capable to access to all the links but the internal
    // one, since Help module is not enabled.
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($test_page_url);

    $this->assertSession()->pageTextContains($external_label);
    $this->assertSession()->pageTextContains($external_link_title);
    $this->assertSession()->pageTextContains($entity_label);
    $this->assertSession()->pageTextContains($entity_link_title);
    $this->assertSession()->pageTextContains($admin_label);
    $this->assertSession()->pageTextContains($admin_link_title);
    $this->assertSession()->pageTextNotContains($help_label);
    $this->assertSession()->pageTextNotContains($help_link_title);

    // Normal user should not have access only to the external link.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($test_page_url);

    $this->assertSession()->pageTextContains($external_label);
    $this->assertSession()->pageTextContains($external_link_title);
    $this->assertSession()->pageTextNotContains($entity_label);
    $this->assertSession()->pageTextNotContains($entity_link_title);
    $this->assertSession()->pageTextNotContains($admin_label);
    $this->assertSession()->pageTextNotContains($admin_link_title);
    $this->assertSession()->pageTextNotContains($help_label);
    $this->assertSession()->pageTextNotContains($help_link_title);

    // Enable Help module and grant permissions to admin user.
    // Admin user should be capable to access to all the links
    \Drupal::service('module_installer')->install(['help']);
    $this->adminUser->addRole($this->drupalCreateRole(['access help pages']))->save();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet($test_page_url);

    $this->assertSession()->pageTextContains($external_label);
    $this->assertSession()->pageTextContains($external_link_title);
    $this->assertSession()->pageTextContains($entity_label);
    $this->assertSession()->pageTextContains($entity_link_title);
    $this->assertSession()->pageTextContains($admin_label);
    $this->assertSession()->pageTextContains($admin_link_title);
    $this->assertSession()->pageTextContains($help_label);
    $this->assertSession()->pageTextContains($help_link_title);

    // Normal user should not have access only to the external link.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($test_page_url);

    $this->assertSession()->pageTextContains($external_label);
    $this->assertSession()->pageTextContains($external_link_title);
    $this->assertSession()->pageTextNotContains($entity_label);
    $this->assertSession()->pageTextNotContains($entity_link_title);
    $this->assertSession()->pageTextNotContains($admin_label);
    $this->assertSession()->pageTextNotContains($admin_link_title);
    $this->assertSession()->pageTextNotContains($help_label);
    $this->assertSession()->pageTextNotContains($help_link_title);
  }

  /**
   * Adds a Navigation Link Block to the sidebar.
   *
   * @param string $label
   *   The block label.
   * @param string $link_title
   *   The link title.
   * @param string $link_uri
   *   The link uri.
   * @param string $link_icon
   *   The link icon CSS class.
   */
  protected function appendNavigationLinkBlock(string $label, string $link_title, string $link_uri, string $link_icon): void {
    $section_storage_manager = \Drupal::service('plugin.manager.layout_builder.section_storage');
    $cacheability = new CacheableMetadata();
    $contexts = [
      'navigation' => new Context(ContextDefinition::create('string'), 'navigation'),
    ];
    /** @var \Drupal\layout_builder\SectionListInterface $section_list */
    $section_list = $section_storage_manager->findByContext($contexts, $cacheability);
    $section = $section_list->getSection(0);

    $section->appendComponent(new SectionComponent(\Drupal::service('uuid')->generate(), 'content', [
      'id' => 'navigation_link',
      'label' => $label,
      'label_display' => '1',
      'provider' => 'navigation',
      'context_mapping' => [],
      'title' => $link_title,
      'uri' => $link_uri,
      'icon_class' => $link_icon,
    ]));

    $section_list->save();
  }

}
