<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Cacheable dependency object for use in tests.
 */
class TestCacheableDependency implements CacheableDependencyInterface {

  /**
   * The cache contexts.
   */
  protected array $contexts;

  /**
   * The cache tags.
   */
  protected array $tags;

  /**
   * The cache maximum age.
   */
  protected int $maxAge;

  public function __construct(array $contexts, array $tags, $max_age) {
    $this->contexts = $contexts;
    $this->tags = $tags;
    $this->maxAge = $max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return $this->maxAge;
  }

}
