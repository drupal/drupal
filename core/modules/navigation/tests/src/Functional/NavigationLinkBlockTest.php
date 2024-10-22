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
  protected static $modules = ['navigation', 'test_page_test', 'block'];

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
    ]);

    // Create additional users to test caching modes.
    $this->normalUser = $this->drupalCreateUser([
      'access navigation',
    ]);

    // Add programmatically a link block to the navigation.
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
      'label' => 'Admin Main Page',
      'label_display' => '0',
      'provider' => 'navigation',
      'context_mapping' => [],
      'title' => 'Navigation Settings',
      'uri' => 'internal:/admin/config/user-interface/navigation/settings',
      'icon_class' => 'admin-link',
    ]));

    $section_list->save();
  }

  /**
   * Test output of the link navigation with regards to caching and contents.
   */
  public function testNavigationLinkBlock(): void {

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
    $link_selector = '.admin-toolbar__item .toolbar-button--icon--admin-link';
    $this->assertSession()->elementNotExists('css', $link_selector);

    // Login as a different user, UI should update.
    $this->drupalLogin($this->adminUser);
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->drupalGet(Url::fromRoute('navigation.settings'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', $link_selector);
    $this->assertSession()
      ->elementTextContains('css', $link_selector, 'Navigation Settings');
    // The link should link to the admin page.
    $link = $this->getSession()->getPage()->find('named', [
      'link',
      'Navigation Settings',
    ]);
    $this->assertStringContainsString('/admin/config/user-interface/navigation/settings', $link->getAttribute('href'));
  }

}
