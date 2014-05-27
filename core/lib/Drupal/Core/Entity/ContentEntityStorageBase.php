<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityStorageBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\PrepareCacheInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldInstanceConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ContentEntityStorageBase extends EntityStorageBase implements FieldableEntityStorageInterface {

  /**
   * The entity bundle key.
   *
   * @var string|bool
   */
  protected $bundleKey = FALSE;

  /**
   * Constructs a ContentEntityStorageBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);

    $this->bundleKey = $this->entityType->getKey('bundle');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // We have to determine the bundle first.
    $bundle = FALSE;
    if ($this->bundleKey) {
      if (!isset($values[$this->bundleKey])) {
        throw new EntityStorageException(String::format('Missing bundle for entity type @type', array('@type' => $this->entityTypeId)));
      }
      $bundle = $values[$this->bundleKey];
    }
    $entity = new $this->entityClass(array(), $this->entityTypeId, $bundle);

    foreach ($entity as $name => $field) {
      if (isset($values[$name])) {
        $entity->$name = $values[$name];
      }
      elseif (!array_key_exists($name, $values)) {
        $entity->get($name)->applyDefaultValue();
      }
      unset($values[$name]);
    }

    // Set any passed values for non-defined fields also.
    foreach ($values as $name => $value) {
      $entity->$name = $value;
    }
    return $entity;
  }

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
   */
  protected function loadFieldItems(array $entities) {
    if (empty($entities)) {
      return;
    }

    $age = static::FIELD_LOAD_CURRENT;
    foreach ($entities as $entity) {
      if (!$entity->isDefaultRevision()) {
        $age = static::FIELD_LOAD_REVISION;
        break;
      }
    }

    // Only the most current revision of non-deleted fields for cacheable entity
    // types can be cached.
    $load_current = $age == static::FIELD_LOAD_CURRENT;
    $use_cache = $load_current && $this->entityType->isFieldDataCacheable();

    // Assume all entities will need to be queried. Entities found in the cache
    // will be removed from the list.
    $queried_entities = $entities;

    // Fetch available entities from cache, if applicable.
    if ($use_cache) {
      // Build the list of cache entries to retrieve.
      $cids = array();
      foreach ($entities as $id => $entity) {
        $cids[] = "field:{$this->entityTypeId}:$id";
      }
      $cache = \Drupal::cache('entity')->getMultiple($cids);
      // Put the cached field values back into the entities and remove them from
      // the list of entities to query.
      foreach ($entities as $id => $entity) {
        $cid = "field:{$this->entityTypeId}:$id";
        if (isset($cache[$cid])) {
          unset($queried_entities[$id]);
          foreach ($cache[$cid]->data as $langcode => $values) {
            $translation = $entity->getTranslation($langcode);
            // We do not need to worry about field translatability here, the
            // translation object will manage that automatically.
            foreach ($values as $field_name => $items) {
              $translation->$field_name = $items;
            }
          }
        }
      }
    }

    // Fetch other entities from their storage location.
    if ($queried_entities) {
      // Let the storage actually load the values.
      $this->doLoadFieldItems($queried_entities, $age);

      // Build cache data.
      // @todo: Improve this logic to avoid instantiating field objects once
      // the field logic is improved to not do that anyway.
      if ($use_cache) {
        foreach ($queried_entities as $id => $entity) {
          $data = array();
          foreach ($entity->getTranslationLanguages() as $langcode => $language) {
            $translation = $entity->getTranslation($langcode);
            foreach ($translation as $field_name => $items) {
              if ($items->getFieldDefinition() instanceof FieldInstanceConfigInterface && !$items->isEmpty()) {
                foreach ($items as $delta => $item) {
                  // If the field item needs to prepare the cache data, call the
                  // corresponding method, otherwise use the values as cache
                  // data.
                  if ($item instanceof PrepareCacheInterface) {
                    $data[$langcode][$field_name][$delta] = $item->getCacheData();
                  }
                  else {
                    $data[$langcode][$field_name][$delta] = $item->getValue();
                  }
                }
              }
            }
          }
          $cid = "field:{$this->entityTypeId}:$id";
          \Drupal::cache('entity')->set($cid, $data, Cache::PERMANENT, array('entity_field_info' => TRUE));
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
    $this->doSaveFieldItems($entity, $update);

    if ($update) {
      $entity_type = $entity->getEntityType();
      if ($entity_type->isFieldDataCacheable()) {
        \Drupal::cache('entity')->delete('field:' . $entity->getEntityTypeId() . ':' . $entity->id());
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
    $this->doDeleteFieldItems($entity);

    $entity_type = $entity->getEntityType();
    if ($entity_type->isFieldDataCacheable()) {
      \Drupal::cache('entity')->delete('field:' . $entity->getEntityTypeId() . ':' . $entity->id());
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
    $this->doDeleteFieldItemsRevision($entity);
  }

  /**
   * Loads values of configurable fields for a group of entities.
   *
   * This is the method that holds the actual storage logic.
   *
   * @param array $entities
   *   An array of entities keyed by entity ID.
   * @param int $age
   *   EntityStorageInterface::FIELD_LOAD_CURRENT to load the most
   *   recent revision for all fields, or
   *   EntityStorageInterface::FIELD_LOAD_REVISION to load the version
   *   indicated by each entity.
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
  public function onFieldCreate(FieldConfigInterface $field) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldUpdate(FieldConfigInterface $field) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldDelete(FieldConfigInterface $field) { }

  /**
   * {@inheritdoc}
   */
  public function onInstanceCreate(FieldInstanceConfigInterface $instance) { }

  /**
   * {@inheritdoc}
   */
  public function onInstanceUpdate(FieldInstanceConfigInterface $instance) { }

  /**
   * {@inheritdoc}
   */
  public function onInstanceDelete(FieldInstanceConfigInterface $instance) { }

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
  public function onFieldItemsPurge(EntityInterface $entity, FieldInstanceConfigInterface $instance) {
    if ($values = $this->readFieldItemsToPurge($entity, $instance)) {
      $items = \Drupal::typedDataManager()->create($instance, $values, $instance->getName(), $entity);
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
   * @param \Drupal\field\FieldInstanceConfigInterface $instance
   *   The field instance.
   *
   * @return array
   *   The field values, in their canonical array format (numerically indexed
   *   array of items, each item being a property/value array).
   */
  abstract protected function readFieldItemsToPurge(EntityInterface $entity, FieldInstanceConfigInterface $instance);

  /**
   * Removes field data from storage during purge.
   *
   * @param EntityInterface $entity
   *   The entity whose values are being purged.
   * @param FieldInstanceConfigInterface $instance
   *   The field whose values are bing purged.
   */
  abstract protected function purgeFieldItems(EntityInterface $entity, FieldInstanceConfigInterface $instance);

  /**
   * {@inheritdoc}
   */
  public function onFieldPurge(FieldConfigInterface $field) { }

  /**
   * Checks translation statuses and invoke the related hooks if needed.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being saved.
   */
  protected function invokeTranslationHooks(ContentEntityInterface $entity) {
    $translations = $entity->getTranslationLanguages(FALSE);
    $original_translations = $entity->original->getTranslationLanguages(FALSE);
    $all_translations = array_keys($translations + $original_translations);

    // Notify modules of translation insertion/deletion.
    foreach ($all_translations as $langcode) {
      if (isset($translations[$langcode]) && !isset($original_translations[$langcode])) {
        $this->invokeHook('translation_insert', $entity->getTranslation($langcode));
      }
      elseif (!isset($translations[$langcode]) && isset($original_translations[$langcode])) {
        $this->invokeHook('translation_delete', $entity->getTranslation($langcode));
      }
    }
  }

  /**
   * Invokes a method on the Field objects within an entity.
   *
   * @param string $method
   *   The method name.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   */
  protected function invokeFieldMethod($method, ContentEntityInterface $entity) {
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $translation = $entity->getTranslation($langcode);
      foreach ($translation->getProperties(TRUE) as $field) {
        $field->$method();
      }
    }
  }

}
