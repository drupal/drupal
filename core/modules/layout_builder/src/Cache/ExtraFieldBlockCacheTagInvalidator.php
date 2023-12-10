<?php

namespace Drupal\layout_builder\Cache;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Provides a cache tag invalidator that clears the block cache.
 *
 * @internal
 *   Tagged services are internal.
 */
class ExtraFieldBlockCacheTagInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * Constructs a new ExtraFieldBlockCacheTagInvalidator.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager.
   */
  public function __construct(protected BlockManagerInterface $blockManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    if (in_array('entity_field_info', $tags, TRUE)) {
      if ($this->blockManager instanceof CachedDiscoveryInterface) {
        $this->blockManager->clearCachedDefinitions();
      }
    }
  }

}
