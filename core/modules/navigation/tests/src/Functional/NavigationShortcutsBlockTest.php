<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Url;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;

/**
 * Tests for \Drupal\navigation\Plugin\Block\NavigationShortcutsBlock.
 *
 * @group navigation
 */
class NavigationShortcutsBlockTest extends PageCacheTagsTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation', 'shortcut', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests visibility and cacheability of shortcuts in the navigation bar.
   */
  public function testNavigationBlock(): void {
    $this->drupalPlaceBlock('page_title_block', ['id' => 'title']);

    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->verifyPageCache($test_page_url, 'MISS');
    $this->verifyPageCache($test_page_url, 'HIT');

    // Ensure that without enabling the shortcuts-in-page-title-link feature
    // in the theme, the shortcut_list cache tag is not added to the page.
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access navigation',
      'administer shortcuts',
      'access shortcuts',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/system/cron');
    $expected_cache_tags = array_merge([
      'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
      'block_view',
      'config:block.block.title',
      'config:block_list',
      'config:navigation.settings',
      'config:navigation.block_layout',
      'config:shortcut.set.default',
      'config:system.menu.admin',
      'config:system.menu.content',
      'config:system.menu.navigation-user-links',
      'http_response',
      'rendered',
    ], $admin_user->getCacheTags());
    $this->assertCacheTags($expected_cache_tags);

    \Drupal::configFactory()
      ->getEditable('stark.settings')
      ->set('third_party_settings.shortcut.module_link', TRUE)
      ->save(TRUE);

    // Add cron to the default shortcut set, now the shortcut list cache tag
    // is expected.
    $this->drupalGet('admin/config/system/cron');
    $this->clickLink('Add to Default shortcuts');
    $expected_cache_tags[] = 'config:shortcut_set_list';
    $this->assertCacheTags($expected_cache_tags);

    // Verify that users without the 'access shortcuts' permission can't see the
    // shortcuts.
    $this->drupalLogin($this->drupalCreateUser(['access navigation']));
    $this->assertSession()->pageTextNotContains('Shortcuts');
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');

    // Verify that users without the 'administer site configuration' permission
    // can't see the cron shortcut nor the shortcuts navigation item.
    $this->drupalLogin($this->drupalCreateUser([
      'access navigation',
      'access shortcuts',
    ]));
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->pageTextNotContains('Shortcuts');
    $this->assertSession()->linkNotExists('Cron');

    // Create a role with access to shortcuts as well as the necessary
    // permissions to see specific shortcuts.
    $site_configuration_role = $this->drupalCreateRole([
      'access navigation',
      'access shortcuts',
      'administer site configuration',
      'access administration pages',
      'configure navigation layout',
    ]);

    // Create two different users with the same role to assert that the second
    // user has a cache hit despite the user cache context, as
    // the returned cache contexts include those from lazy-builder content.
    $site_configuration_user1 = $this->drupalCreateUser();
    $site_configuration_user1->addRole($site_configuration_role)->save();
    $site_configuration_user2 = $this->drupalCreateUser();
    $site_configuration_user2->addRole($site_configuration_role)->save();

    $this->drupalLogin($site_configuration_user1);
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertCacheContexts(['user', 'url.query_args:_wrapper_format', 'session', 'route']);
    $this->assertSession()->pageTextContains('Shortcuts');
    $this->assertSession()->linkExists('Cron');

    $this->drupalLogin($site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertCacheContexts(['user', 'url.query_args:_wrapper_format', 'session', 'route']);
    $this->assertSession()->pageTextContains('Shortcuts');
    $this->assertSession()->linkExists('Cron');

    // Add another shortcut.
    $shortcut = Shortcut::create([
      'shortcut_set' => 'default',
      'title' => 'Llama',
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin/config']],
    ]);
    $shortcut->save();

    // The shortcuts are displayed in a lazy builder, so the page is still a
    // cache HIT but shows the new shortcut immediately.
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkExists('Llama');

    // Update the shortcut title and assert that it is updated.
    $shortcut->set('title', 'Alpaca');
    $shortcut->save();
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkExists('Alpaca');

    // Delete the shortcut and assert that the link is gone.
    $shortcut->delete();
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkNotExists('Alpaca');

    // Add a new Shortcut Set with a single link.
    $new_set = ShortcutSet::create([
      'id' => 'llama-set',
      'label' => 'Llama Set',
    ]);
    $new_set->save();
    $new_shortcut = Shortcut::create([
      'shortcut_set' => 'llama-set',
      'title' => 'New Llama',
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin/config']],
    ]);
    $new_shortcut->save();

    // Assign the new shortcut set to user 2 and confirm that links are changed
    // automatically.
    \Drupal::entityTypeManager()
      ->getStorage('shortcut_set')
      ->assignUser($new_set, $site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkExists('New Llama');
    // Confirm that links for user 1 have not been affected.
    $this->drupalLogin($site_configuration_user1);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkNotExists('New Llama');
    // Confirm that removing assignment automatically changes the links too.
    $this->drupalLogin($site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkExists('New Llama');
    \Drupal::entityTypeManager()
      ->getStorage('shortcut_set')
      ->unassignUser($site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkNotExists('New Llama');
    // Confirm that deleting a shortcut set automatically changes the links too.
    \Drupal::entityTypeManager()
      ->getStorage('shortcut_set')
      ->assignUser($new_set, $site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkExists('New Llama');
    \Drupal::entityTypeManager()
      ->getStorage('shortcut_set')
      ->delete([$new_set]);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Cron');
    $this->assertSession()->linkNotExists('New Llama');

    // Verify that block disappears gracefully when shortcut module is disabled.
    // Shortcut entities has to be removed first.
    $link_storage = \Drupal::entityTypeManager()->getStorage('shortcut');
    $link_storage->delete($link_storage->loadMultiple());
    \Drupal::service('module_installer')->uninstall(['shortcut']);
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Shortcuts');

    // Confirm that Navigation Blocks page is working.
    // @see https://www.drupal.org/project/drupal/issues/3445184
    $this->drupalGet('/admin/config/user-interface/navigation-block');
    $this->assertSession()->statusCodeEquals(200);
  }

}
