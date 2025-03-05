<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;

// cspell:ignore navigationuser linksuserwrapper

/**
 * Tests for \Drupal\navigation\Plugin\NavigationBlock\NavigationUserBlock.
 *
 * @group navigation
 */
class NavigationUserBlockTest extends PageCacheTagsTestBase {

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
   * @var object
   */
  protected $adminUser;

  /**
   * An authenticated user to test navigation block caching.
   *
   * @var object
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
      'access navigation',
    ]);

    // Create additional users to test caching modes.
    $this->normalUser = $this->drupalCreateUser([
      'access navigation',
    ]);

    // Note that we don't need to setup a user navigation block b/c it's
    // installed by default.
  }

  /**
   * Test output of user navigation block with regards to caching and contents.
   */
  public function testNavigationUserBlock(): void {
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
    // We should see the users name in the navigation menu.
    $rendered_user_name = $this->cssSelect('[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')[0]->getText();
    $this->assertEquals($this->normalUser->getDisplayName(), $rendered_user_name);

    // We should see all three user links in the page.
    $link_labels = ['View profile', 'Edit profile', 'Log out'];
    $block = $this->assertSession()->elementExists('css', sprintf('.toolbar-block:contains("%s")', $rendered_user_name));
    foreach ($link_labels as $link_label) {
      $links = $block->findAll('named', ['link', $link_label]);
      $this->assertCount(1, $links, sprintf('Found %s links with label %s.', count($links), $link_label));
    }
    // The Edit profile link should link to the users edit profile page.
    $links = $this->getSession()->getPage()->findAll('named', ['link', 'Edit profile']);
    $this->assertStringContainsString('/user/edit', $links[0]->getAttribute('href'));

    // Login as a different user, UI should update.
    $this->drupalLogin($this->adminUser);
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $rendered_user_name = $this->cssSelect('[aria-controls="navigation-link-navigationuser-linksuserwrapper"] > .toolbar-button__label')[0]->getText();
    $this->assertEquals($this->adminUser->getDisplayName(), $rendered_user_name);
    // The Edit profile link should link to the users edit profile page.
    $links = $this->getSession()->getPage()->findAll('named', ['link', 'Edit profile']);
    $this->assertStringContainsString('/user/edit', $links[0]->getAttribute('href'));
  }

  /**
   * Test output of user navigation block when there are no menu items.
   */
  public function testNavigationUserBlockFallback(): void {
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $items = [
      'navigation.user_links.user.page',
      'navigation.user_links.user.edit',
      'navigation.user_links.user.logout',
    ];

    foreach ($items as $item) {
      $front_page_link = $menu_link_manager->getDefinition($item);
      $front_page_link['enabled'] = FALSE;
      $menu_link_manager->updateDefinition($item, $front_page_link);
    }

    $this->drupalLogin($this->normalUser);
    // We should see the users name in the navigation menu in a link.
    $rendered_user_name = $this->cssSelect('a.toolbar-button--icon--navigation-user-links-user-wrapper')[0]->getText();
    $this->assertEquals($this->normalUser->getDisplayName(), $rendered_user_name);
  }

}
