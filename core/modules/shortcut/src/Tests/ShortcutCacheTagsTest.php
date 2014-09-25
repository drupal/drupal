<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\ShortcutCacheTagsTest.
 */

namespace Drupal\shortcut\Tests;

use Drupal\shortcut\Entity\Shortcut;
use Drupal\system\Tests\Entity\EntityCacheTagsTestBase;

/**
 * Tests the Shortcut entity's cache tags.
 *
 * @group shortcut
 */
class ShortcutCacheTagsTest extends EntityCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('shortcut');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to customize shortcut links, so that we
    // can verify the cache tags of cached versions of shortcuts.
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('customize shortcut links');
    $user_role->grantPermission('access shortcuts');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" shortcut.
    $shortcut = Shortcut::create(array(
      'set' => 'default',
      'title' => t('Llama'),
      'weight' => 0,
      'path' => 'admin',
    ));
    $shortcut->save();

    return $shortcut;
  }

  /**
   * Tests that when creating a shortcut, the shortcut set tag is invalidated.
   */
  public function testEntityCreation() {
    // Create a cache entry that is tagged with a shortcut set cache tag.
    $cache_tags = array('shortcut_set:default');
    \Drupal::cache('render')->set('foo', 'bar', \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    // Verify a cache hit.
    $this->verifyRenderCache('foo', array('shortcut_set:default'));

    // Now create a shortcut entity in that shortcut set.
    $this->createEntity();

    // Verify a cache miss.
    $this->assertFalse(\Drupal::cache('render')->get('foo'), 'Creating a new shortcut invalidates the cache tag of the shortcut set.');
  }

}
