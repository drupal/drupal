<?php

declare(strict_types=1);

namespace Drupal\Tests\Traits\Core\Config;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Allows writing raw config data for testing purposes.
 *
 * The writes do not use schema to normalize the data.
 */
trait RawConfigWriterTrait {

  /**
   * Writes raw config data.
   *
   * @param string $name
   *   The config name.
   * @param array $data
   *   The config data to write.
   */
  protected function writeRawConfig(string $name, array $data): void {
    $storage = \Drupal::service('config.storage');
    $storage->write($name, $data);
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['config:' . $name]);
    \Drupal::configFactory()->clearStaticCache();
  }

  /**
   * Writes a config entity without using the entity system or schema.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity to write.
   */
  protected function writeRawConfigEntity(ConfigEntityInterface $entity): void {
    $this->writeRawConfig($entity->getConfigDependencyName(), $entity->toArray());
    $tags = Cache::mergeTags(
      $entity->getCacheTags(),
      $entity->getEntityType()->getListCacheTags(),
      $entity->getEntityType()->hasKey('bundle') ? $entity->getEntityType()->getBundleListCacheTags($entity->bundle()) : [],
    );
    if (!empty($tags)) {
      \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);
    }
    \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->resetCache([$entity->id()]);
  }

}
