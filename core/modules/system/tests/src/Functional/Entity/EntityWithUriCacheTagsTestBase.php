<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Provides helper methods for Entity cache tags tests; for entities with URIs.
 */
abstract class EntityWithUriCacheTagsTestBase extends EntityCacheTagsTestBase {

  /**
   * Tests cache tags presence and invalidation of the entity at its URI.
   *
   * Tests the following cache tags:
   * - "<entity type>_view"
   * - "<entity_type>:<entity ID>"
   */
  public function testEntityUri(): void {
    $entity_url = $this->entity->toUrl();
    $entity_type = $this->entity->getEntityTypeId();

    // Selects the view mode that will be used.
    $view_mode = $this->selectViewMode($entity_type);

    // The default cache contexts for rendered entities.
    $entity_cache_contexts = $this->getDefaultCacheContexts();

    // Generate the standardized entity cache tags.
    $cache_tag = $this->entity->getCacheTags();
    $view_cache_tag = \Drupal::entityTypeManager()->getViewBuilder($entity_type)->getCacheTags();
    $render_cache_tag = 'rendered';

    $this->verifyPageCache($entity_url, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($entity_url, 'HIT');

    // Also verify the existence of an entity render cache entry, if this entity
    // type supports render caching.
    if (\Drupal::entityTypeManager()->getDefinition($entity_type)->isRenderCacheable()) {
      $cache_keys = ['entity_view', $entity_type, $this->entity->id(), $view_mode];
      $expected_cache_tags = Cache::mergeTags($cache_tag, $view_cache_tag);
      $expected_cache_tags = Cache::mergeTags($expected_cache_tags, $this->getAdditionalCacheTagsForEntity($this->entity));
      $expected_cache_tags = Cache::mergeTags($expected_cache_tags, [$render_cache_tag]);
      $this->verifyRenderCache($cache_keys, $expected_cache_tags, (new CacheableMetadata())->setCacheContexts($entity_cache_contexts));
    }

    // Verify that after modifying the entity, there is a cache miss.
    $this->entity->save();
    $this->verifyPageCache($entity_url, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_url, 'HIT');

    // Verify that after modifying the entity's display, there is a cache miss.
    $entity_display = \Drupal::service('entity_display.repository')->getViewDisplay($entity_type, $this->entity->bundle(), $view_mode);
    $entity_display->save();
    $this->verifyPageCache($entity_url, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_url, 'HIT');

    if ($bundle_entity_type_id = $this->entity->getEntityType()->getBundleEntityType()) {
      // Verify that after modifying the corresponding bundle entity, there is a
      // cache miss.
      $bundle_entity = $this->container->get('entity_type.manager')
        ->getStorage($bundle_entity_type_id)
        ->load($this->entity->bundle());
      $bundle_entity->save();
      $this->verifyPageCache($entity_url, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_url, 'HIT');
    }

    if ($this->entity->getEntityType()->get('field_ui_base_route')) {
      // Verify that after modifying a configurable field on the entity, there
      // is a cache miss.
      $field_storage_name = $this->entity->getEntityTypeId() . '.configurable_field';
      $field_storage = FieldStorageConfig::load($field_storage_name);
      $field_storage->save();
      $this->verifyPageCache($entity_url, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_url, 'HIT');

      // Verify that after modifying a configurable field on the entity, there
      // is a cache miss.
      $field_name = $this->entity->getEntityTypeId() . '.' . $this->entity->bundle() . '.configurable_field';
      $field = FieldConfig::load($field_name);
      $field->save();
      $this->verifyPageCache($entity_url, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_url, 'HIT');
    }

    // Verify that after invalidating the entity's cache tag directly, there is
    // a cache miss.
    Cache::invalidateTags($this->entity->getCacheTagsToInvalidate());
    $this->verifyPageCache($entity_url, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_url, 'HIT');

    // Verify that after invalidating the generic entity type's view cache tag
    // directly, there is a cache miss.
    Cache::invalidateTags($view_cache_tag);
    $this->verifyPageCache($entity_url, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_url, 'HIT');

    // Verify that after deleting the entity, there is a cache miss.
    $this->entity->delete();
    $this->verifyPageCache($entity_url, 'MISS');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Gets the default cache contexts for rendered entities.
   *
   * @return array
   *   The default cache contexts for rendered entities.
   */
  protected function getDefaultCacheContexts() {
    // For url.site, see
    // \Drupal\Core\Entity\Controller\EntityViewController::view().
    return ['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions', 'url.site'];
  }

}
