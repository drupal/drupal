<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional;

use Drupal\Core\Url;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\system\Functional\Entity\EntityCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Tests the Shortcut entity's cache tags.
 *
 * @group shortcut
 */
class ShortcutCacheTagsTest extends EntityCacheTagsTestBase {
  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'toolbar',
    'shortcut',
    'test_page_test',
    'block',
  ];

  /**
   * User with permission to administer shortcuts.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access toolbar',
      'access shortcuts',
      'administer site configuration',
      'administer shortcuts',
      'administer themes',
    ]);

    // Give anonymous users permission to customize shortcut links, so that we
    // can verify the cache tags of cached versions of shortcuts.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $user_role->grantPermission('customize shortcut links');
    $user_role->grantPermission('access shortcuts');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" shortcut.
    $shortcut = Shortcut::create([
      'shortcut_set' => 'default',
      'title' => 'Llama',
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin']],
    ]);
    $shortcut->save();

    return $shortcut;
  }

  /**
   * Tests visibility and cacheability of shortcuts in the toolbar.
   */
  public function testToolbar(): void {
    $this->drupalPlaceBlock('page_title_block', ['id' => 'title']);

    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->verifyPageCache($test_page_url, 'MISS');
    $this->verifyPageCache($test_page_url, 'HIT');

    // Ensure that without enabling the shortcuts-in-page-title-link feature
    // in the theme, the shortcut_list cache tag is not added to the page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/system/cron');
    $expected_cache_tags = [
      'block_view',
      'config:block.block.title',
      'config:block_list',
      'config:shortcut.set.default',
      'config:system.menu.admin',
      'config:system.theme',
      'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
      'rendered',
    ];
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
    $this->drupalLogin($this->drupalCreateUser(['access toolbar']));
    $this->assertSession()->linkNotExists('Shortcuts');
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');

    // Verify that users without the 'administer site configuration' permission
    // can't see the cron shortcut but can see shortcuts toolbar tab.
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar',
      'access shortcuts',
    ]));
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->linkExists('Shortcuts');
    $this->assertSession()->linkNotExists('Cron');

    // Create a role with access to shortcuts as well as the necessary
    // permissions to see specific shortcuts.
    $site_configuration_role = $this->drupalCreateRole([
      'access toolbar',
      'access shortcuts',
      'administer site configuration',
      'access administration pages',
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
    $this->assertCacheContexts(['session', 'user', 'url.query_args:_wrapper_format']);
    $this->assertSession()->linkExists('Shortcuts');
    $this->assertSession()->linkExists('Cron');

    $this->drupalLogin($site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertCacheContexts(['session', 'user', 'url.query_args:_wrapper_format']);
    $this->assertSession()->linkExists('Shortcuts');
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

    // Assign the new shortcut set to user 2 and confirm that links are
    // changed automatically.
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
  }

  /**
   * Tests visibility and cacheability of shortcuts in the block.
   */
  public function testBlock(): void {
    $this->drupalPlaceBlock('page_title_block', ['id' => 'title']);
    $this->drupalPlaceBlock('shortcuts', [
      'id' => 'shortcuts',
      'label' => 'Shortcuts Block',
    ]);

    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->verifyPageCache($test_page_url, 'MISS');
    $this->verifyPageCache($test_page_url, 'HIT');

    // Ensure that without enabling the shortcuts-in-page-title-link feature
    // in the theme, the shortcut_list cache tag is not added to the page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/system/cron');
    $expected_cache_tags = [
      'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
      'block_view',
      'config:block.block.shortcuts',
      'config:block.block.title',
      'config:block_list',
      'config:shortcut.set.default',
      'config:system.menu.admin',
      'config:system.theme',
      'rendered',
    ];
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
    $this->drupalLogin($this->drupalCreateUser());
    $this->assertSession()->pageTextNotContains('Shortcuts Block');
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');

    // Verify that users without the 'administer site configuration' permission
    // can't see the cron shortcut but can see the block.
    $this->drupalLogin($this->drupalCreateUser([
      'access shortcuts',
    ]));
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertSession()->pageTextContains('Shortcuts Block');
    $this->assertSession()->linkNotExists('Cron');

    // Create a role with access to shortcuts as well as the necessary
    // permissions to see specific shortcuts.
    $site_configuration_role = $this->drupalCreateRole([
      'access shortcuts',
      'administer site configuration',
      'access administration pages',
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
    $this->assertCacheContexts(['user', 'url.query_args:_wrapper_format']);
    $this->assertSession()->pageTextContains('Shortcuts Block');
    $this->assertSession()->linkExists('Cron');

    $this->drupalLogin($site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertCacheContexts(['user', 'url.query_args:_wrapper_format']);
    $this->assertSession()->pageTextContains('Shortcuts Block');
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
  }

}
