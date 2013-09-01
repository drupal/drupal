<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\FieldableEntityStorageControllerBase.
 */

namespace Drupal\Core\Entity;

use Drupal\field\FieldInterface;
use Drupal\field\FieldInstanceInterface;
use Symfony\Component\DependencyInjection\Container;

abstract class FieldableEntityStorageControllerBase extends EntityStorageControllerBase implements FieldableEntityStorageControllerInterface {

  /**
   * Loads values of configurable fields for a group of entities.
   *
   * Loads all fields for each entity object in a group of a single entity type.
   * The loaded field values are added directly to the entity objects.
   *
   * This method is a wrapper that handles the field data cache. Subclasses
   * need to implement the doLoadFieldItems() method with the actual storage
   * logic.
   *
   * @param array $entities
   *   An array of entities keyed by entity ID.
   * @param int $age
   *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
   *   FIELD_LOAD_REVISION to load the version indicated by each entity.
   */
  protected function loadFieldItems(array $entities, $age) {
    if (empty($entities)) {
      return;
    }

    // Only the most current revision of non-deleted fields for cacheable entity
    // types can be cached.
    $load_current = $age == FIELD_LOAD_CURRENT;
    $info = entity_get_info($this->entityType);
    $use_cache = $load_current && $info['field_cache'];

    // Ensure we are working with a BC mode entity.
    foreach ($entities as $id => $entity) {
      $entities[$id] = $entity->getBCEntity();
    }

    // Assume all entities will need to be queried. Entities found in the cache
    // will be removed from the list.
    $queried_entities = $entities;

    // Fetch available entities from cache, if applicable.
    if ($use_cache) {
      // Build the list of cache entries to retrieve.
      $cids = array();
      foreach ($entities as $id => $entity) {
        $cids[] = "field:{$this->entityType}:$id";
      }
      $cache = cache('field')->getMultiple($cids);
      // Put the cached field values back into the entities and remove them from
      // the list of entities to query.
      foreach ($entities as $id => $entity) {
        $cid = "field:{$this->entityType}:$id";
        if (isset($cache[$cid])) {
          unset($queried_entities[$id]);
          foreach ($cache[$cid]->data as $field_name => $values) {
            $entity->$field_name = $values;
          }
        }
      }
    }

    // Fetch other entities from their storage location.
    if ($queried_entities) {
      // Let the storage controller actually load the values.
      $this->doLoadFieldItems($queried_entities, $age);

      // Invoke the field type's prepareCache() method.
      foreach ($queried_entities as $entity) {
        $this->invokeFieldItemPrepareCache($entity);
      }

      // Build cache data.
      if ($use_cache) {
        foreach ($queried_entities as $id => $entity) {
          $data = array();
          $instances = field_info_instances($this->entityType, $entity->bundle());
          foreach ($instances as $instance) {
            $data[$instance['field_name']] = $queried_entities[$id]->{$instance['field_name']};
          }
          $cid = "field:{$this->entityType}:$id";
          cache('field')->set($cid, $data);
        }
      }
    }
  }

  /**
   * Saves values of configurable fields for an entity.
   *
   * This method is a wrapper that handles the field data cache. Subclasses
   * need to implement the doSaveFieldItems() method with the actual storage
   * logic.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param bool $update
   *   TRUE if the entity is being updated, FALSE if it is being inserted.
   */
  protected function saveFieldItems(EntityInterface $entity, $update = TRUE) {
    // Ensure we are working with a BC mode entity.
    $entity = $entity->getBCEntity();

    $this->doSaveFieldItems($entity, $update);

    if ($update) {
      $entity_info = $entity->entityInfo();
      if ($entity_info['field_cache']) {
        cache('field')->delete('field:' . $entity->entityType() . ':' . $entity->id());
      }
    }
  }

  /**
   * Deletes values of configurable fields for all revisions of an entity.
   *
   * This method is a wrapper that handles the field data cache. Subclasses
   * need to implement the doDeleteFieldItems() method with the actual storage
   * logic.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function deleteFieldItems(EntityInterface $entity) {
    // Ensure we are working with a BC mode entity.
    $entity = $entity->getBCEntity();

    $this->doDeleteFieldItems($entity);

    $entity_info = $entity->entityInfo();
    if ($entity_info['field_cache']) {
      cache('field')->delete('field:' . $entity->entityType() . ':' . $entity->id());
    }
  }

  /**
   * Deletes values of configurable fields for a single revision of an entity.
   *
   * This method is a wrapper that handles the field data cache. Subclasses
   * need to implement the doDeleteFieldItemsRevision() method with the actual
   * storage logic.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity. It must have a revision ID attribute.
   */
  protected function deleteFieldItemsRevision(EntityInterface $entity) {
    $this->doDeleteFieldItemsRevision($entity->getBCEntity());
  }

  /**
   * Loads values of configurable fields for a group of entities.
   *
   * This is the method that holds the actual storage logic.
   *
   * @param array $entities
   *   An array of entities keyed by entity ID.
   * @param int $age
   *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
   *   FIELD_LOAD_REVISION to load the version indicated by each entity.
   */
  abstract protected function doLoadFieldItems($entities, $age);

  /**
   * Saves values of configurable fields for an entity.
   *
   * This is the method that holds the actual storage logic.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param bool $update
   *   TRUE if the entity is being updated, FALSE if it is being inserted.
   */
  abstract protected function doSaveFieldItems(EntityInterface $entity, $update);

  /**
   * Deletes values of configurable fields for all revisions of an entity.
   *
   * This is the method that holds the actual storage logic.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  abstract protected function doDeleteFieldItems(EntityInterface $entity);

  /**
   * Deletes values of configurable fields for a single revision of an entity.
   *
   * This is the method that holds the actual storage logic.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  abstract protected function doDeleteFieldItemsRevision(EntityInterface $entity);

  /**
   * {@inheritdoc}
   */
  public function onFieldCreate(FieldInterface $field) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldUpdate(FieldInterface $field) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldDelete(FieldInterface $field) { }

  /**
   * {@inheritdoc}
   */
  public function onInstanceCreate(FieldInstanceInterface $instance) { }

  /**
   * {@inheritdoc}
   */
  public function onInstanceUpdate(FieldInstanceInterface $instance) { }

  /**
   * {@inheritdoc}
   */
  public function onInstanceDelete(FieldInstanceInterface $instance) { }

  /**
   * {@inheritdoc}
   */
  public function onBundleCreate($bundle) { }

  /**
   * {@inheritdoc}
   */
  public function onBundleRename($bundle, $bundle_new) { }

  /**
   * {@inheritdoc}
   */
  public function onBundleDelete($bundle) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldItemsPurge(EntityInterface $entity, FieldInstanceInterface $instance) {
    if ($values = $this->readFieldItemsToPurge($entity, $instance)) {
      $field = $instance->getField();
      $definition = _field_generate_entity_field_definition($field, $instance);
      $items = \Drupal::typedData()->create($definition, $values, $field->getFieldName(), $entity);
      $items->delete();
    }
    $this->purgeFieldItems($entity, $instance);
  }

  /**
   * Reads values to be purged for a single field of a single entity.
   *
   * This method is called during field data purge, on fields for which
   * onFieldDelete() or onFieldInstanceDelete() has previously run.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\field\FieldInstanceInterface $instance
   *   The field instance.
   *
   * @return array
   *   The field values, in their canonical array format (numerically indexed
   *   array of items, each item being a property/value array).
   */
  abstract protected function readFieldItemsToPurge(EntityInterface $entity, FieldInstanceInterface $instance);

  /**
   * Removes field data from storage during purge.
   *
   * @param EntityInterface $entity
   *   The entity whose values are being purged.
   * @param FieldInstanceInterface $instance
   *   The field whose values are bing purged.
   */
  abstract protected function purgeFieldItems(EntityInterface $entity, FieldInstanceInterface $instance);

  /**
   * {@inheritdoc}
   */
  public function onFieldPurge(FieldInterface $field) { }

}
