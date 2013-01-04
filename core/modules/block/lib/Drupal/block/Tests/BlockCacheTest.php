<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockCacheTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test block caching.
 */
class BlockCacheTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  protected $admin_user;
  protected $normal_user;
  protected $normal_user_alt;

  public static function getInfo() {
    return array(
      'name' => 'Block caching',
      'description' => 'Test block caching.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an admin user, log in and enable test blocks.
    $this->admin_user = $this->drupalCreateUser(array('administer blocks', 'access administration pages'));
    $this->drupalLogin($this->admin_user);

    // Create additional users to test caching modes.
    $this->normal_user = $this->drupalCreateUser();
    $this->normal_user_alt = $this->drupalCreateUser();
    // Sync the roles, since drupalCreateUser() creates separate roles for
    // the same permission sets.
    $this->normal_user_alt->roles = $this->normal_user->roles;
    $this->normal_user_alt->save();

    // Enable our test block.
    $this->theme = variable_get('theme_default', 'stark');
    $block = array();
    $block['machine_name'] = $this->randomName(8);
    $block['region'] = 'sidebar_first';
    $this->block = $block;
    $this->drupalPost('admin/structure/block/manage/test_cache/' . $this->theme,  $block, t('Save block'));
  }

  /**
   * Test DRUPAL_CACHE_PER_ROLE.
   */
  function testCachePerRole() {
    $this->setCacheMode(DRUPAL_CACHE_PER_ROLE);

    // Enable our test block. Set some content for it to display.
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);
    $this->drupalLogin($this->normal_user);
    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    // Change the content, but the cached copy should still be served.
    $old_content = $current_content;
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);
    $this->drupalGet('');
    $this->assertText($old_content, 'Block is served from the cache.');

    // Clear the cache and verify that the stale data is no longer there.
    cache_invalidate_tags(array('content' => TRUE));
    $this->drupalGet('');
    $this->assertNoText($old_content, 'Block cache clear removes stale cache data.');
    $this->assertText($current_content, 'Fresh block content is displayed after clearing the cache.');

    // Test whether the cached data is served for the correct users.
    $old_content = $current_content;
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($old_content, 'Anonymous user does not see content cached per-role for normal user.');

    $this->drupalLogin($this->normal_user_alt);
    $this->drupalGet('');
    $this->assertText($old_content, 'User with the same roles sees per-role cached content.');

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('');
    $this->assertNoText($old_content, 'Admin user does not see content cached per-role for normal user.');

    $this->drupalLogin($this->normal_user);
    $this->drupalGet('');
    $this->assertText($old_content, 'Block is served from the per-role cache.');
  }

  /**
   * Test DRUPAL_CACHE_GLOBAL.
   */
  function testCacheGlobal() {
    $this->setCacheMode(DRUPAL_CACHE_GLOBAL);
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);

    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    $old_content = $current_content;
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);

    $this->drupalLogout();
    $this->drupalGet('user');
    $this->assertText($old_content, 'Block content served from global cache.');
  }

  /**
   * Test DRUPAL_NO_CACHE.
   */
  function testNoCache() {
    $this->setCacheMode(DRUPAL_NO_CACHE);
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);

    // If DRUPAL_NO_CACHE has no effect, the next request would be cached.
    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    // A cached copy should not be served.
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);
    $this->drupalGet('');
    $this->assertText($current_content, 'DRUPAL_NO_CACHE prevents blocks from being cached.');
  }

  /**
   * Test DRUPAL_CACHE_PER_USER.
   */
  function testCachePerUser() {
    $this->setCacheMode(DRUPAL_CACHE_PER_USER);
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);
    $this->drupalLogin($this->normal_user);

    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    $old_content = $current_content;
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);

    $this->drupalGet('');
    $this->assertText($old_content, 'Block is served from per-user cache.');

    $this->drupalLogin($this->normal_user_alt);
    $this->drupalGet('');
    $this->assertText($current_content, 'Per-user block cache is not served for other users.');

    $this->drupalLogin($this->normal_user);
    $this->drupalGet('');
    $this->assertText($old_content, 'Per-user block cache is persistent.');
  }

  /**
   * Test DRUPAL_CACHE_PER_PAGE.
   */
  function testCachePerPage() {
    $this->setCacheMode(DRUPAL_CACHE_PER_PAGE);
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);

    $this->drupalGet('node');
    $this->assertText($current_content, 'Block content displays on the node page.');

    $old_content = $current_content;
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);

    $this->drupalGet('user');
    $this->assertNoText($old_content, 'Block content cached for the node page does not show up for the user page.');
    $this->drupalGet('node');
    $this->assertText($old_content, 'Block content cached for the node page.');
  }

  /**
   * Private helper method to set the test block's cache mode.
   */
  private function setCacheMode($cache_mode) {
    $block = $this->block;
    $block['config_id'] = 'plugin.core.block.' . $this->theme . '.' . $block['machine_name'];
    $block_config = config($block['config_id']);
    $block_config->set('cache', $cache_mode);
    $block_config->save();

    $instance = block_load($block['config_id']);
    $config = $instance->getConfig();
    if ($config['cache'] != $cache_mode) {
      $this->fail(t('Unable to set cache mode to %mode. Current mode: %current_mode', array('%mode' => $cache_mode, '%current_mode' => $config['cache'])));
    }
    $this->assertEqual($config['cache'], $cache_mode, t("Test block's database entry updated to DRUPAL_NO_CACHE."));
  }
}
