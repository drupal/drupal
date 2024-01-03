<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Cacheable dependency object for use in tests.
 */
class TestCacheableDependency implements CacheableDependencyInterface {

  protected array $contexts;
  protected array $tags;
  protected $maxAge;

  public function __construct(array $contexts, array $tags, $max_age) {
    $this->contexts = $contexts;
    $this->tags = $tags;
    $this->maxAge = $max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->maxAge;
  }

}
