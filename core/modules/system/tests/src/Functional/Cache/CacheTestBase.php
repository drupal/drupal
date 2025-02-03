<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Cache;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides helper methods for cache tests.
 */
abstract class CacheTestBase extends BrowserTestBase {

  /**
   * The default bin for the cache item.
   *
   * @var string
   */
  protected $defaultBin = 'render';

  /**
   * The default cache ID.
   *
   * @var string
   */
  protected $defaultCid = 'test_temporary';

  /**
   * The cache contents default value.
   *
   * @var string
   */
  protected $defaultValue = 'CacheTest';

  /**
   * Checks whether or not a cache entry exists.
   *
   * @param string $cid
   *   The cache id.
   * @param mixed $var
   *   The variable the cache should contain.
   * @param string|null $bin
   *   The bin the cache item was stored in.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function checkCacheExists($cid, $var, $bin = NULL): bool {
    if ($bin == NULL) {
      $bin = $this->defaultBin;
    }

    $cached = \Drupal::cache($bin)->get($cid);

    return isset($cached->data) && $cached->data == $var;
  }

  /**
   * Asserts that a cache entry exists.
   *
   * @param string $message
   *   Message to display.
   * @param string|null $var
   *   The variable the cache should contain.
   * @param string|null $cid
   *   The cache id.
   * @param string|null $bin
   *   The bin the cache item was stored in.
   */
  protected function assertCacheExists($message, $var = NULL, $cid = NULL, $bin = NULL) {
    if ($bin == NULL) {
      $bin = $this->defaultBin;
    }
    if ($cid == NULL) {
      $cid = $this->defaultCid;
    }
    if ($var == NULL) {
      $var = $this->defaultValue;
    }

    $this->assertTrue($this->checkCacheExists($cid, $var, $bin), $message);
  }

  /**
   * Asserts that a cache entry has been removed.
   *
   * @param string $message
   *   Message to display.
   * @param string|null $cid
   *   The cache id.
   * @param string|null $bin
   *   The bin the cache item was stored in.
   */
  public function assertCacheRemoved($message, $cid = NULL, $bin = NULL) {
    if ($bin == NULL) {
      $bin = $this->defaultBin;
    }
    if ($cid == NULL) {
      $cid = $this->defaultCid;
    }

    $cached = \Drupal::cache($bin)->get($cid);
    $this->assertFalse($cached, $message);
  }

}
