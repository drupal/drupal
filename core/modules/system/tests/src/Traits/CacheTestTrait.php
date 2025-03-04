<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Traits;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\VariationCacheInterface;

/**
 * Provides helper methods for interacting with cache backends.
 */
trait CacheTestTrait {

  /**
   * Retrieves the render cache backend as a variation cache.
   *
   * This is how Drupal\Core\Render\RenderCache uses the render cache backend.
   *
   * @return \Drupal\Core\Cache\VariationCacheInterface
   *   The render cache backend as a variation cache.
   */
  protected function getRenderVariationCache(): VariationCacheInterface {
    /** @var \Drupal\Core\Cache\VariationCacheFactoryInterface $variation_cache_factory */
    $variation_cache_factory = \Drupal::service('variation_cache_factory');
    return $variation_cache_factory->get('render');
  }

  /**
   * Retrieves the default cache backend as a variation cache.
   *
   * @return \Drupal\Core\Cache\VariationCacheInterface
   *   The default cache backend as a variation cache.
   */
  protected function getDefaultVariationCache(): VariationCacheInterface {
    /** @var \Drupal\Core\Cache\VariationCacheFactoryInterface $variation_cache_factory */
    $variation_cache_factory = \Drupal::service('variation_cache_factory');
    return $variation_cache_factory->get('default');
  }

  /**
   * Verify that a given render cache entry exists with the correct cache tags.
   *
   * @param string[] $keys
   *   The render cache item keys.
   * @param array $tags
   *   An array of expected cache tags.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The initial cacheability the item was rendered with.
   */
  protected function verifyRenderCache(array $keys, array $tags, CacheableDependencyInterface $cacheability): void {
    $this->verifyCache($this->getRenderVariationCache(), $keys, $tags, $cacheability);
  }

  /**
   * Verify that a given default cache entry exists with the correct cache tags.
   *
   * @param string[] $keys
   *   The cache item keys.
   * @param array $tags
   *   An array of expected cache tags.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The initial cacheability for the item.
   */
  protected function verifyDefaultCache(array $keys, array $tags, CacheableDependencyInterface $cacheability): void {
    $this->verifyCache($this->getDefaultVariationCache(), $keys, $tags, $cacheability);
  }

  /**
   * Verify that a given cache entry exists, with the correct cache tags.
   *
   * @param \Drupal\Core\Cache\VariationCacheInterface $cache_bin
   *   The cache bin to check.
   * @param string[] $keys
   *   The cache item keys.
   * @param array $tags
   *   An array of expected cache tags.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The initial cacheability for the item.
   */
  private function verifyCache(VariationCacheInterface $cache_bin, array $keys, array $tags, CacheableDependencyInterface $cacheability): void {
    $cache_entry = $cache_bin->get($keys, $cacheability);
    $this->assertInstanceOf(\stdClass::class, $cache_entry);
    sort($cache_entry->tags);
    sort($tags);
    $this->assertSame($cache_entry->tags, $tags);
  }

}
