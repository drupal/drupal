<?php

namespace Drupal\Tests\shortcut\Functional;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Url;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\system\Functional\Entity\EntityCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
      'title' => t('Llama'),
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin']],
    ]);
    $shortcut->save();

    return $shortcut;
  }

  /**
   * Tests that when creating a shortcut, the shortcut set tag is invalidated.
   */
  public function testEntityCreation() {
    // Create a cache entry that is tagged with a shortcut set cache tag.
    $cache_tags = ['config:shortcut.set.default'];
    \Drupal::cache('render')->set('foo', 'bar', CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    // Verify a cache hit.
    $this->verifyRenderCache('foo', $cache_tags);

    // Now create a shortcut entity in that shortcut set.
    $this->createEntity();

    // Verify a cache miss.
    $this->assertFalse(\Drupal::cache('render')->get('foo'), 'Creating a new shortcut invalidates the cache tag of the shortcut set.');
  }

  /**
   * Tests visibility and cacheability of shortcuts in the toolbar.
   */
  public function testToolbar() {
    $this->drupalPlaceBlock('page_title_block', ['id' => 'title']);

    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->verifyPageCache($test_page_url, 'MISS');
    $this->verifyPageCache($test_page_url, 'HIT');

    // Ensure that without enabling the shortcuts-in-page-title-link feature
    // in the theme, the shortcut_list cache tag is not added to the page.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/config/system/cron');
    $expected_cache_tags = [
      'block_view',
      'config:block.block.title',
      'config:block_list',
      'config:shortcut.set.default',
      'config:system.menu.admin',
      'config:user.role.authenticated',
      'rendered',
      'user:' . $this->rootUser->id(),
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
    $this->assertNoLink('Shortcuts');
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
    $this->assertLink('Shortcuts');
    $this->assertNoLink('Cron');

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
    $site_configuration_user1->addRole($site_configuration_role);
    $site_configuration_user1->save();
    $site_configuration_user2 = $this->drupalCreateUser();
    $site_configuration_user2->addRole($site_configuration_role);
    $site_configuration_user2->save();

    $this->drupalLogin($site_configuration_user1);
    $this->verifyDynamicPageCache($test_page_url, 'MISS');
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertCacheContexts(['user', 'url.query_args:_wrapper_format']);
    $this->assertLink('Shortcuts');
    $this->assertLink('Cron');

    $this->drupalLogin($site_configuration_user2);
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertCacheContexts(['user', 'url.query_args:_wrapper_format']);
    $this->assertLink('Shortcuts');
    $this->assertLink('Cron');

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
    $this->assertLink('Cron');
    $this->assertLink('Llama');

    // Update the shortcut title and assert that it is updated.
    $shortcut->set('title', 'Alpaca');
    $shortcut->save();
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertLink('Cron');
    $this->assertLink('Alpaca');

    // Delete the shortcut and assert that the link is gone.
    $shortcut->delete();
    $this->verifyDynamicPageCache($test_page_url, 'HIT');
    $this->assertLink('Cron');
    $this->assertNoLink('Alpaca');
  }

}
