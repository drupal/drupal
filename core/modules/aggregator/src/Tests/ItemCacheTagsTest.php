<?php

namespace Drupal\aggregator\Tests;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;
use Drupal\system\Tests\Entity\EntityCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Item entity's cache tags.
 *
 * @group aggregator
 */
class ItemCacheTagsTest extends EntityCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('aggregator');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to access feeds, so that we can verify
    // the cache tags of cached versions of feed items.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $user_role->grantPermission('access news feeds');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" feed.
    $feed = Feed::create(array(
      'title' => 'Camelids',
      'url' => 'https://groups.drupal.org/not_used/167169',
      'refresh' => 900,
      'checked' => 1389919932,
      'description' => 'Drupal Core Group feed',
    ));
    $feed->save();

    // Create a "Llama" aggregator feed item.
    $item = Item::create(array(
      'fid' => $feed->id(),
      'title' => t('Llama'),
      'path' => 'https://www.drupal.org/',
    ));
    $item->save();

    return $item;
  }

  /**
   * Tests that when creating a feed item, the feed tag is invalidated.
   */
  public function testEntityCreation() {
    // Create a cache entry that is tagged with a feed cache tag.
    \Drupal::cache('render')->set('foo', 'bar', \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT, $this->entity->getCacheTags());

    // Verify a cache hit.
    $this->verifyRenderCache('foo', array('aggregator_feed:1'));

    // Now create a feed item in that feed.
    Item::create(array(
      'fid' => $this->entity->getFeedId(),
      'title' => t('Llama 2'),
      'path' => 'https://groups.drupal.org/',
    ))->save();

    // Verify a cache miss.
    $this->assertFalse(\Drupal::cache('render')->get('foo'), 'Creating a new feed item invalidates the cache tag of the feed.');
  }

}
