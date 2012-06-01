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
    parent::setUp(array('block', 'block_test'));

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
    $edit['blocks[block_test_test_cache][region]'] = 'sidebar_first';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
  }

  /**
   * Test DRUPAL_CACHE_PER_ROLE.
   */
  function testCachePerRole() {
    $this->setCacheMode(DRUPAL_CACHE_PER_ROLE);

    // Enable our test block. Set some content for it to display.
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);
    $this->drupalLogin($this->normal_user);
    $this->drupalGet('');
    $this->assertText($current_content, t('Block content displays.'));

    // Change the content, but the cached copy should still be served.
    $old_content = $current_content;
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);
    $this->drupalGet('');
    $this->assertText($old_content, t('Block is served from the cache.'));

    // Clear the cache and verify that the stale data is no longer there.
    cache_clear_all();
    $this->drupalGet('');
    $this->assertNoText($old_content, t('Block cache clear removes stale cache data.'));
    $this->assertText($current_content, t('Fresh block content is displayed after clearing the cache.'));

    // Test whether the cached data is served for the correct users.
    $old_content = $current_content;
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($old_content, t('Anonymous user does not see content cached per-role for normal user.'));

    $this->drupalLogin($this->normal_user_alt);
    $this->drupalGet('');
    $this->assertText($old_content, t('User with the same roles sees per-role cached content.'));

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('');
    $this->assertNoText($old_content, t('Admin user does not see content cached per-role for normal user.'));

    $this->drupalLogin($this->normal_user);
    $this->drupalGet('');
    $this->assertText($old_content, t('Block is served from the per-role cache.'));
  }

  /**
   * Test DRUPAL_CACHE_GLOBAL.
   */
  function testCacheGlobal() {
    $this->setCacheMode(DRUPAL_CACHE_GLOBAL);
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);

    $this->drupalGet('');
    $this->assertText($current_content, t('Block content displays.'));

    $old_content = $current_content;
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);

    $this->drupalLogout();
    $this->drupalGet('user');
    $this->assertText($old_content, t('Block content served from global cache.'));
  }

  /**
   * Test DRUPAL_NO_CACHE.
   */
  function testNoCache() {
    $this->setCacheMode(DRUPAL_NO_CACHE);
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);

    // If DRUPAL_NO_CACHE has no effect, the next request would be cached.
    $this->drupalGet('');
    $this->assertText($current_content, t('Block content displays.'));

    // A cached copy should not be served.
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);
    $this->drupalGet('');
    $this->assertText($current_content, t('DRUPAL_NO_CACHE prevents blocks from being cached.'));
  }

  /**
   * Test DRUPAL_CACHE_PER_USER.
   */
  function testCachePerUser() {
    $this->setCacheMode(DRUPAL_CACHE_PER_USER);
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);
    $this->drupalLogin($this->normal_user);

    $this->drupalGet('');
    $this->assertText($current_content, t('Block content displays.'));

    $old_content = $current_content;
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);

    $this->drupalGet('');
    $this->assertText($old_content, t('Block is served from per-user cache.'));

    $this->drupalLogin($this->normal_user_alt);
    $this->drupalGet('');
    $this->assertText($current_content, t('Per-user block cache is not served for other users.'));

    $this->drupalLogin($this->normal_user);
    $this->drupalGet('');
    $this->assertText($old_content, t('Per-user block cache is persistent.'));
  }

  /**
   * Test DRUPAL_CACHE_PER_PAGE.
   */
  function testCachePerPage() {
    $this->setCacheMode(DRUPAL_CACHE_PER_PAGE);
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);

    $this->drupalGet('node');
    $this->assertText($current_content, t('Block content displays on the node page.'));

    $old_content = $current_content;
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);

    $this->drupalGet('user');
    $this->assertNoText($old_content, t('Block content cached for the node page does not show up for the user page.'));
    $this->drupalGet('node');
    $this->assertText($old_content, t('Block content cached for the node page.'));
  }

  /**
   * Private helper method to set the test block's cache mode.
   */
  private function setCacheMode($cache_mode) {
    db_update('block')
      ->fields(array('cache' => $cache_mode))
      ->condition('module', 'block_test')
      ->execute();

    $current_mode = db_query("SELECT cache FROM {block} WHERE module = 'block_test'")->fetchField();
    if ($current_mode != $cache_mode) {
      $this->fail(t('Unable to set cache mode to %mode. Current mode: %current_mode', array('%mode' => $cache_mode, '%current_mode' => $current_mode)));
    }
  }
}
