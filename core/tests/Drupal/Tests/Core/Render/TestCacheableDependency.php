<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Cacheable dependency object for use in tests.
 */
class TestCacheableDependency implements CacheableDependencyInterface {

  public function __construct(
    protected array $contexts,
    protected array $tags,
    protected int $maxAge,
  ) {
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
