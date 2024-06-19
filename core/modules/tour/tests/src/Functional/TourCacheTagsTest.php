<?php

declare(strict_types=1);

namespace Drupal\Tests\tour\Functional;

use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;
use Drupal\tour\Entity\Tour;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Tour entity's cache tags.
 *
 * @group tour
 * @group legacy
 */
class TourCacheTagsTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tour', 'tour_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Give anonymous users permission to view nodes, so that we can verify the
    // cache tags of cached versions of node pages.
    Role::load(RoleInterface::ANONYMOUS_ID)->grantPermission('access tour')
      ->save();
  }

  /**
   * Tests cache tags presence and invalidation of the Tour entity.
   *
   * Tests the following cache tags:
   * - 'tour:<tour ID>'
   */
  public function testRenderedTour(): void {
    $url = Url::fromRoute('tour_test.1');

    // Prime the page cache.
    $this->verifyPageCache($url, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $expected_tags = [
      'config:tour.tour.tour-test',
      'config:user.role.anonymous',
      'http_response',
      'rendered',
    ];
    $this->verifyPageCache($url, 'HIT', $expected_tags);

    // Verify that after modifying the tour, there is a cache miss.
    Tour::load('tour-test')->save();
    $this->verifyPageCache($url, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($url, 'HIT', $expected_tags);

    // Verify that after deleting the tour, there is a cache miss.
    Tour::load('tour-test')->delete();
    $this->verifyPageCache($url, 'MISS');

    // Verify a cache hit.
    $expected_tags = [
      'config:user.role.anonymous',
      'http_response',
      'rendered',
    ];
    $this->verifyPageCache($url, 'HIT', $expected_tags);
  }

}
