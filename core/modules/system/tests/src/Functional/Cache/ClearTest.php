<?php

namespace Drupal\Tests\system\Functional\Cache;

/**
 * Tests our clearing is done the proper way.
 *
 * @group Cache
 */
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Rebuilder;

class ClearTest extends CacheTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    $this->defaultBin = 'render';
    $this->defaultValue = $this->randomMachineName(10);

    parent::setUp();
  }

  /**
   * Tests \Drupal\Core\Cache\Rebuilder::rebuildAll().
   */
  public function testrebuildAll() {
    // Create cache entries for each flushed cache bin.
    $bins = Cache::getBins();
    $this->assertNotEmpty($bins, 'Cache::getBins() returned bins to flush.');
    foreach ($bins as $bin => $cache_backend) {
      $cid = 'test_cid_clear' . $bin;
      $cache_backend->set($cid, $this->defaultValue);
    }

    // Remove all caches then make sure that they are cleared.
    Rebuilder::rebuildAll();

    foreach ($bins as $bin => $cache_backend) {
      $cid = 'test_cid_clear' . $bin;
      $this->assertFalse($this->checkCacheExists($cid, $this->defaultValue, $bin), new FormattableMarkup('All cache entries removed from @bin.', ['@bin' => $bin]));
    }
  }

}
