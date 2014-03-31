<?php

/**
 * @file
 * Contains Drupal\plugin_test\Plugin\CachedMockBlockManager.
 */

namespace Drupal\plugin_test\Plugin;

use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Defines a plugin manager used by Plugin API cache decorator web tests.
 */
class CachedMockBlockManager extends MockBlockManager {

  /**
   * Adds a cache decorator to the MockBlockManager's discovery.
   *
   * @see \Drupal\plugin_test\Plugin\MockBlockManager::__construct().
   */
  public function __construct() {
    parent::__construct();
    // The CacheDecorator allows us to cache these plugin definitions for
    // quicker retrieval. In this case we are generating a cache key by
    // language.
    $this->discovery = new CacheDecorator($this->discovery, 'mock_block:' . \Drupal::languageManager()->getCurrentLanguage()->id, 'default',  1542646800, array('plugin_test'));
  }
}
