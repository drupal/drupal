<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\ShortcutCacheTagsTest.
 */

namespace Drupal\shortcut\Tests;

use Drupal\shortcut\Entity\Shortcut;
use Drupal\system\Tests\Entity\EntityCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

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
    $shortcut = Shortcut::create(array(
      'shortcut_set' => 'default',
      'title' => t('Llama'),
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin']],
    ));
    $shortcut->save();

    return $shortcut;
  }

  /**
   * Tests that when creating a shortcut, the shortcut set tag is invalidated.
   */
  public function testEntityCreation() {
    // Create a cache entry that is tagged with a shortcut set cache tag.
    $cache_tags = ['config:shortcut.set.default'];
    \Drupal::cache('render')->set('foo', 'bar', \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    // Verify a cache hit.
    $this->verifyRenderCache('foo', $cache_tags);

    // Now create a shortcut entity in that shortcut set.
    $this->createEntity();

    // Verify a cache miss.
    $this->assertFalse(\Drupal::cache('render')->get('foo'), 'Creating a new shortcut invalidates the cache tag of the shortcut set.');
  }

}
