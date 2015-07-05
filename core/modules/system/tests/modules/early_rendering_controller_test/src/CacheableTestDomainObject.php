<?php

/**
 * @file
 * Contains \Drupal\early_rendering_controller_test\AttachmentsTestDomainObject.
 */

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Cache\CacheableDependencyInterface;

class CacheableTestDomainObject extends TestDomainObject implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
