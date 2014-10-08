<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\CacheablePluginInterface.
 */

namespace Drupal\views\Plugin;

/**
 * Provides information whether and how the specific Views plugin is cacheable.
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
