<?php

declare(strict_types=1);

namespace Drupal\Core\Render\Placeholder;

use Drupal\Core\Render\RenderCacheInterface;

/**
 * Looks up placeholders in the render cache and returns those we could find.
 */
class CachedStrategy implements PlaceholderStrategyInterface {

  public function __construct(
    protected readonly PlaceholderStrategyInterface $placeholderStrategy,
    protected readonly RenderCacheInterface $renderCache,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {
    $return = $this->renderCache->getMultiple($placeholders);
    if ($return) {
      $return = $this->processNestedPlaceholders($return);
    }

    return $return;
  }

  /**
   * Fetch any nested placeholders from cache.
   *
   * Placeholders returned from cache may have placeholders in #attached, which
   * can themselves be fetched from the cache. By recursively processing the
   * placeholders here, we're able to use multiple cache get to fetch the cache
   * items at each level of recursion.
   */
  private function processNestedPlaceholders(array $placeholders): array {
    $sets = [];
    foreach ($placeholders as $key => $placeholder) {
      if (!empty($placeholder['#attached']['placeholders'])) {
        $sets[] = $placeholder['#attached']['placeholders'];
      }
    }
    if ($sets) {
      $cached = $this->renderCache->getMultiple(...array_merge($sets));
      if ($cached) {
        $cached = $this->processNestedPlaceholders($cached);
        foreach ($placeholders as $key => $placeholder) {
          if (!empty($placeholder['#attached']['placeholders'])) {
            $placeholders[$key]['#attached']['placeholders'] = array_replace($placeholder['#attached']['placeholders'], $cached);
          }
        }
      }
    }
    return $placeholders;
  }

}
