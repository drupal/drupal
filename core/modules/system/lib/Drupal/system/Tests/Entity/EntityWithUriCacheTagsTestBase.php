<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\system\Tests\Entity\EntityCacheTagsTestBase;

/**
 * Provides helper methods for Entity cache tags tests; for entities with URIs.
 */
abstract class EntityWithUriCacheTagsTestBase extends EntityCacheTagsTestBase {

  /**
   * Returns the additional (non-standard) cache tags for the tested entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be tested, as created by createEntity().
   * @return array
   *   An array of the additional cache tags.
   *
   * @see \Drupal\system\Tests\Entity\EntityCacheTagsTestBase::createEntity()
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $entity) {
    return array();
  }

  /**
   * Tests cache tags presence and invalidation of the entity at its URI.
   *
   * Tests the following cache tags:
   * - "<entity type>_view:1"
   * - "<entity_type>:<entity ID>"
   */
  public function testEntityUri() {
    $entity_path = $this->entity->getSystemPath();
    $entity_type = $this->entity->getEntityTypeId();

    // Generate the standardized entity cache tags.
    $cache_tag = $entity_type . ':' . $this->entity->id();
    $view_cache_tag = $entity_type . '_view:1';


    // Prime the page cache.
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $tags = array('content:1', $view_cache_tag, $cache_tag);
    $this->verifyPageCache($entity_path, 'HIT');

    // Also verify the existence of an entity render cache entry, if this entity
    // type supports render caching.
    if (\Drupal::entityManager()->getDefinition($entity_type)->isRenderCacheable()) {
      $cid = 'entity_view:' . $entity_type . ':' . $this->entity->id() . ':full:stark:r.anonymous';
      $cache_entry = \Drupal::cache()->get($cid);
      $expected_cache_tags = array_merge(array($view_cache_tag, $cache_tag), $this->getAdditionalCacheTagsForEntity($this->entity));
      $this->assertIdentical($cache_entry->tags, $expected_cache_tags);
    }

    // Verify that after modifying the entity, there is a cache miss.
    $this->pass("Test modification of entity.", 'Debug');
    $this->entity->save();
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_path, 'HIT');


    // Verify that after modifying the entity's "full" display, there is a cache
    // miss.
    $this->pass("Test modification of entity's 'full' display.", 'Debug');
    $entity_display = entity_get_display($entity_type, $this->entity->bundle(), 'full');
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


    if ($this->entity->getEntityType()->isFieldable()) {
      // Verify that after modifying a configurable field on the entity, there
      // is a cache miss.
      $this->pass("Test modification of entity's configurable field.", 'Debug');
      $field_name = $this->entity->getEntityTypeId() . '.configurable_field';
      $field = entity_load('field_config', $field_name);
      $field->save();
      $this->verifyPageCache($entity_path, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_path, 'HIT');


      // Verify that after modifying a configurable field instance on the
      // entity, there is a cache miss.
      $this->pass("Test modification of entity's configurable field instance.", 'Debug');
      $field_instance_name = $this->entity->getEntityTypeId() . '.' . $this->entity->bundle() . '.configurable_field';
      $field_instance = entity_load('field_instance_config', $field_instance_name);
      $field_instance->save();
      $this->verifyPageCache($entity_path, 'MISS');

      // Verify a cache hit.
      $this->verifyPageCache($entity_path, 'HIT');
    }


    // Verify that after invalidating the entity's cache tag directly, there is
    // a cache miss.
    $this->pass("Test invalidation of entity's cache tag.", 'Debug');
    Cache::invalidateTags(array($entity_type => array($this->entity->id())));
    $this->verifyPageCache($entity_path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($entity_path, 'HIT');


    // Verify that after invalidating the generic entity type's view cache tag
    // directly, there is a cache miss.
    $this->pass("Test invalidation of entity's 'view' cache tag.", 'Debug');
    Cache::invalidateTags(array($entity_type . '_view' => TRUE));
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
