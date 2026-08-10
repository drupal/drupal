<?php

declare(strict_types=1);

namespace Drupal\options;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides allowed values for a list field.
 */
class OptionsAllowedValues implements OptionsAllowedValuesInterface {

  /**
   * Cache tag set on all cached allowed values.
   */
  const CACHE_TAG = 'options_allowed_values';

  public function __construct(
    #[Autowire(service: 'cache.memory')]
    protected CacheBackendInterface $cache,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getAllowedValues(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL): array {
    $cid = $this->getCid([
      $definition->getTargetEntityTypeId(),
      $definition->getName(),
      $entity ? 'entity' : 'any',
    ]);

    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $callback = $definition->getSetting('allowed_values_function');

    // If $cacheable is FALSE, then the allowed values are not cached.
    // \Drupal\options_test\OptionsAllowedValues::dynamicValues() offers an
    // example of generating dynamic and uncached values.
    // @see \Drupal\options_test\OptionsAllowedValues::dynamicValues()
    $cacheable = TRUE;
    if (!empty($callback) && is_callable($callback)) {
      $allowedValues = $callback($definition, $entity, $cacheable);
    }
    else {
      $allowedValues = $definition->getSetting('allowed_values') ?? [];
    }

    if ($cacheable) {
      $tags = Cache::mergeTags($definition->getCacheTags(), [static::CACHE_TAG]);
      $this->cache->set($cid, $allowedValues, CacheBackendInterface::CACHE_PERMANENT, $tags);
    }

    return $allowedValues;
  }

  /**
   * Returns the CID used to cache the options allowed values.
   *
   * @param array $path
   *   The parts identifying the allowed values: the target entity type ID,
   *   the field name and whether an entity is available.
   *
   * @return string
   *   The cache ID.
   */
  protected function getCid(array $path): string {
    return 'options.allowed_values:' . implode(':', $path);
  }

}
