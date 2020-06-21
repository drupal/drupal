<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests block caching.
 *
 * @group block
 */
class BlockCacheTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * An authenticated user to test block caching.
   *
   * @var object
   */
  protected $normalUser;

  /**
   * Another authenticated user to test block caching.
   *
   * @var object
   */
  protected $normalUserAlt;

  /**
   * The block used by this test.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected $block;

  protected function setUp(): void {
    parent::setUp();

    // Create an admin user, log in and enable test blocks.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create additional users to test caching modes.
    $this->normalUser = $this->drupalCreateUser();
    $this->normalUserAlt = $this->drupalCreateUser();
    // Sync the roles, since drupalCreateUser() creates separate roles for
    // the same permission sets.
    $this->normalUserAlt->roles = $this->normalUser->getRoles();
    $this->normalUserAlt->save();

    // Enable our test block.
    $this->block = $this->drupalPlaceBlock('test_cache');
  }

  /**
   * Test "user.roles" cache context.
   */
  public function testCachePerRole() {
    \Drupal::state()->set('block_test.cache_contexts', ['user.roles']);

    // Enable our test block. Set some content for it to display.
    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    // Change the content, but the cached copy should still be served.
    $old_content = $current_content;
    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);
    $this->drupalGet('');
    $this->assertText($old_content, 'Block is served from the cache.');

    // Clear the cache and verify that the stale data is no longer there.
    Cache::invalidateTags(['block_view']);
    $this->drupalGet('');
    $this->assertNoText($old_content, 'Block cache clear removes stale cache data.');
    $this->assertText($current_content, 'Fresh block content is displayed after clearing the cache.');

    // Test whether the cached data is served for the correct users.
    $old_content = $current_content;
    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($old_content, 'Anonymous user does not see content cached per-role for normal user.');

    $this->drupalLogin($this->normalUserAlt);
    $this->drupalGet('');
    $this->assertText($old_content, 'User with the same roles sees per-role cached content.');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('');
    $this->assertNoText($old_content, 'Admin user does not see content cached per-role for normal user.');

    $this->drupalLogin($this->normalUser);
    $this->drupalGet('');
    $this->assertText($old_content, 'Block is served from the per-role cache.');
  }

  /**
   * Test a cacheable block without any additional cache context.
   */
  public function testCachePermissions() {
    // user.permissions is a required context, so a user with different
    // permissions will see a different version of the block.
    \Drupal::state()->set('block_test.cache_contexts', []);

    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);

    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    $old_content = $current_content;
    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);

    $this->drupalGet('user');
    $this->assertText($old_content, 'Block content served from cache.');

    $this->drupalLogout();
    $this->drupalGet('user');
    $this->assertText($current_content, 'Block content not served from cache.');
  }

  /**
   * Test non-cacheable block.
   */
  public function testNoCache() {
    \Drupal::state()->set('block_test.cache_max_age', 0);

    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);

    // If max_age = 0 has no effect, the next request would be cached.
    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    // A cached copy should not be served.
    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);
    $this->drupalGet('');
    $this->assertText($current_content, 'Maximum age of zero prevents blocks from being cached.');
  }

  /**
   * Test "user" cache context.
   */
  public function testCachePerUser() {
    \Drupal::state()->set('block_test.cache_contexts', ['user']);

    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);
    $this->drupalLogin($this->normalUser);

    $this->drupalGet('');
    $this->assertText($current_content, 'Block content displays.');

    $old_content = $current_content;
    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);

    $this->drupalGet('');
    $this->assertText($old_content, 'Block is served from per-user cache.');

    $this->drupalLogin($this->normalUserAlt);
    $this->drupalGet('');
    $this->assertText($current_content, 'Per-user block cache is not served for other users.');

    $this->drupalLogin($this->normalUser);
    $this->drupalGet('');
    $this->assertText($old_content, 'Per-user block cache is persistent.');
  }

  /**
   * Test "url" cache context.
   */
  public function testCachePerPage() {
    \Drupal::state()->set('block_test.cache_contexts', ['url']);

    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);

    $this->drupalGet('test-page');
    $this->assertText($current_content, 'Block content displays on the test page.');

    $old_content = $current_content;
    $current_content = $this->randomMachineName();
    \Drupal::state()->set('block_test.content', $current_content);

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoText($old_content, 'Block content cached for the test page does not show up for the user page.');
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText($old_content, 'Block content cached for the test page.');
  }

}
