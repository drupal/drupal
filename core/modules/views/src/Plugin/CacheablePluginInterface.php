<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\CacheablePluginInterface.
 */

namespace Drupal\views\Plugin;

/**
 * Provides caching information about the result cacheability of views plugins.
 *
 * For caching on the render level, we rely on bubbling of the cache contexts.
 */
interface CacheablePluginInterface {

  /**
   * Returns TRUE if this plugin is cacheable at all.
   *
   * @return bool
   */
  public function isCacheable();

  /**
   * Returns an array of cache contexts, this plugin varies by.
   *
   * Note: This method is called on views safe time, so you do have the
   * configuration available. For example an exposed filter changes its
   * cacheability depending on the URL.
   *
   * @return string[]
   */
  public function getCacheContexts();

}
