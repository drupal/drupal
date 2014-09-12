<?php

/**
 * @file
 * Contains \Drupal\tour\Tests\TourCacheTagsTest.
 */

namespace Drupal\tour\Tests;

use Drupal\system\Tests\Cache\PageCacheTagsTestBase;
use Drupal\tour\Entity\Tour;
use Drupal\user\Entity\Role;

/**
 * Tests the Tour entity's cache tags.
 *
 * @group tour
 */
class TourCacheTagsTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('tour', 'tour_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to view nodes, so that we can verify the
    // cache tags of cached versions of node pages.
    Role::load(DRUPAL_ANONYMOUS_RID)->grantPermission('access tour')
     ->save();
  }

  /**
   * Tests cache tags presence and invalidation of the Tour entity.
   *
   * Tests the following cache tags:
   * - ['tour' => '<tour ID>']
   */
  public function testRenderedTour() {
    $path = 'tour-test-1';

    // Prime the page cache.
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $expected_tags = array(
      'theme:stark',
      'theme_global_settings:1',
      'tour:tour-test',
      'rendered:1',
    );
    $this->verifyPageCache($path, 'HIT', $expected_tags);

    // Verify that after modifying the tour, there is a cache miss.
    $this->pass('Test modification of tour.', 'Debug');
    Tour::load('tour-test')->save();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT', $expected_tags);

    // Verify that after deleting the tour, there is a cache miss.
    $this->pass('Test deletion of tour.', 'Debug');
    Tour::load('tour-test')->delete();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT', array('rendered:1', 'theme:stark', 'theme_global_settings:1'));
  }

}
