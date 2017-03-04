<?php

namespace Drupal\Tests\aggregator\Functional;

use Drupal\aggregator\Entity\Feed;
use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Feed entity's cache tags.
 *
 * @group aggregator
 */
class FeedCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to access feeds, so that we can verify
    // the cache tags of cached versions of feeds.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $user_role->grantPermission('access news feeds');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" feed.
    $feed = Feed::create([
      'title' => 'Llama',
      'url' => 'https://www.drupal.org/',
      'refresh' => 900,
      'checked' => 1389919932,
      'description' => 'Drupal.org',
    ]);
    $feed->save();

    return $feed;
  }

}
