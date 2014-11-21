<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Cache\Cache;
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
  public function testEntityUri() {
    $entity_path = $this->entity->getSystemPath();
    $entity_type = $this->entity->getEntityTypeId();

    // Selects the view mode that will be used.
    $view_mode = $this->selectViewMode($entity_type);

    // Generate the standardized entity cache tags.
    $cache_tag = $this->entity->getCacheTags();
    $view_cache_tag = \Drupal::entityManager()->getViewBuilder($entity_type)->getCacheTags();
    $render_cache_tag = 'rendered';


    $this->pass("Test entity.", 'Debug');
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($entity_path, 'HIT');

    // Also verify the existence of an entity render cache entry, if this entity
    // type supports render caching.
    if (\Drupal::entityManager()->getDefinition($entity_type)->isRenderCacheable()) {
      $cid = 'entity_view:' . $entity_type . ':' . $this->entity->id() . ':' . $view_mode . ':stark:r.anonymous:' . date_default_timezone_get();
      $cache_entry = \Drupal::cache('render')->get($cid);
      $expected_cache_tags = Cache::mergeTags($cache_tag, $view_cache_tag, $this->getAdditionalCacheTagsForEntity($this->entity), array($render_cache_tag));
      $this->verifyRenderCache($cid, $expected_cache_tags);
    }

    // Verify that after modifying the entity, there is a cache miss.
    $this->pass("Test modification of entity.", 'Debug');
    $this->entity->save();
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_path, 'HIT');


    // Verify that after modifying the entity's display, there is a cache miss.
    $this->pass("Test modification of entity's '$view_mode' display.", 'Debug');
    $entity_display = entity_get_display($entity_type, $this->entity->bundle(), $view_mode);
    $entity_display->save();
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_path, 'HIT');


    $bundle_entity_type = $this->entity->getEntityType()->getBundleEntityType();
    if ($bundle_entity_type !== 'bundle') {
      // Verify that after modifying the corresponding bundle entity, there is a
      // cache miss.
      $this->pass("Test modification of entity's bundle entity.", 'Debug');
      $bundle_entity = entity_load($bundle_entity_type, $this->entity->bundle());
      $bundle_entity->save();
      $this->verifyPageCache($entity_path, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_path, 'HIT');
    }


    if ($this->entity->getEntityType()->get('field_ui_base_route')) {
      // Verify that after modifying a configurable field on the entity, there
      // is a cache miss.
      $this->pass("Test modification of entity's configurable field.", 'Debug');
      $field_storage_name = $this->entity->getEntityTypeId() . '.configurable_field';
      $field_storage = entity_load('field_storage_config', $field_storage_name);
      $field_storage->save();
      $this->verifyPageCache($entity_path, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_path, 'HIT');

      // Verify that after modifying a configurable field on the entity, there
      // is a cache miss.
      $this->pass("Test modification of entity's configurable field.", 'Debug');
      $field_name = $this->entity->getEntityTypeId() . '.' . $this->entity->bundle() . '.configurable_field';
      $field = FieldConfig::load($field_name);
      $field->save();
      $this->verifyPageCache($entity_path, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_path, 'HIT');
    }


    // Verify that after invalidating the entity's cache tag directly, there is
    // a cache miss.
    $this->pass("Test invalidation of entity's cache tag.", 'Debug');
    Cache::invalidateTags($this->entity->getCacheTags());
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_path, 'HIT');


    // Verify that after invalidating the generic entity type's view cache tag
    // directly, there is a cache miss.
    $this->pass("Test invalidation of entity's 'view' cache tag.", 'Debug');
    Cache::invalidateTags($view_cache_tag);
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_path, 'HIT');


    // Verify that after deleting the entity, there is a cache miss.
    $this->pass('Test deletion of entity.', 'Debug');
    $this->entity->delete();
    $this->verifyPageCache($entity_path, 'MISS');
    $this->assertResponse(404);
  }

}
