<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityStorageBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ContentEntityStorageBase extends EntityStorageBase implements DynamicallyFieldableEntityStorageInterface {

  /**
   * The entity bundle key.
   *
   * @var string|bool
   */
  protected $bundleKey = FALSE;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a ContentEntityStorageBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, CacheBackendInterface $cache) {
    parent::__construct($entity_type);
    $this->bundleKey = $this->entityType->getKey('bundle');
    $this->entityManager = $entity_manager;
    $this->cacheBackend = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('cache.entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    return (bool) $this->getQuery()
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // We have to determine the bundle first.
    $bundle = FALSE;
    if ($this->bundleKey) {
      if (!isset($values[$this->bundleKey])) {
        throw new EntityStorageException('Missing bundle for entity type ' . $this->entityTypeId);
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
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original) { }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) { }

  /**
   * {@inheritdoc}
   */
  public function purgeFieldData(FieldDefinitionInterface $field_definition, $batch_size) {
    $items_by_entity = $this->readFieldItemsToPurge($field_definition, $batch_size);

    foreach ($items_by_entity as $items) {
      $items->delete();
      $this->purgeFieldItems($items->getEntity(), $field_definition);
    }
    return count($items_by_entity);
  }

  /**
   * Reads values to be purged for a single field.
   *
   * This method is called during field data purge, on fields for which
   * onFieldDefinitionDelete() has previously run.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param $batch_size
   *   The maximum number of field data records to purge before returning.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   An array of field item lists, keyed by entity revision id.
   */
  abstract protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size);

  /**
   * Removes field items from storage per entity during purge.
   *
   * @param ContentEntityInterface $entity
   *   The entity revision, whose values are being purged.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field whose values are bing purged.
   */
  abstract protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition);

  /**
   * {@inheritdoc}
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) { }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    $revision = $this->doLoadRevisionFieldItems($revision_id);

    if ($revision) {
      $entities = [$revision->id() => $revision];
      $this->invokeStorageLoadHook($entities);
      $this->postLoad($entities);
    }

    return $revision;
  }

  /**
   * Actually loads revision field item values from the storage.
   *
   * @param int|string $revision_id
   *   The revision identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The specified entity revision or NULL if not found.
   */
  abstract protected function doLoadRevisionFieldItems($revision_id);

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    if ($entity->isNew()) {
      // Ensure the entity is still seen as new after assigning it an id, while
      // storing its data.
      $entity->enforceIsNew();
      if ($this->entityType->isRevisionable()) {
        $entity->setNewRevision();
      }
      $return = SAVED_NEW;
    }
    else {
      // @todo Consider returning a different value when saving a non-default
      //   entity revision. See https://www.drupal.org/node/2509360.
      $return = $entity->isDefaultRevision() ? SAVED_UPDATED : FALSE;
    }

    $this->populateAffectedRevisionTranslations($entity);
    $this->doSaveFieldItems($entity);

    return $return;
  }

  /**
   * Writes entity field values to the storage.
   *
   * This method is responsible for allocating entity and revision identifiers
   * and updating the entity object with their values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param string[] $names
   *   (optional) The name of the fields to be written to the storage. If an
   *   empty value is passed all field values are saved.
   */
  abstract protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []);

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */

    // Sync the changes made in the fields array to the internal values array.
    $entity->updateOriginalValues();

    return parent::doPreSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    if ($update && $this->entityType->isTranslatable()) {
      $this->invokeTranslationHooks($entity);
    }

    parent::doPostSave($entity, $update);

    // The revision is stored, it should no longer be marked as new now.
    if ($this->entityType->isRevisionable()) {
      $entity->setNewRevision(FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    foreach ($entities as $entity) {
      $this->invokeFieldMethod('delete', $entity);
    }
    $this->doDeleteFieldItems($entities);
  }

  /**
   * Deletes entity field values from the storage.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   An array of entity objects to be deleted.
   */
  abstract protected function doDeleteFieldItems($entities);

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
    if ($revision = $this->loadRevision($revision_id)) {
      // Prevent deletion if this is the default revision.
      if ($revision->isDefaultRevision()) {
        throw new EntityStorageException('Default revision can not be deleted');
      }
      $this->invokeFieldMethod('deleteRevision', $revision);
      $this->doDeleteRevisionFieldItems($revision);
      $this->invokeHook('revision_delete', $revision);
    }
  }

  /**
   * Deletes field values of an entity revision from the storage.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   An entity revision object to be deleted.
   */
  abstract protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision);

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
   * Invokes hook_entity_storage_load().
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   List of entities, keyed on the entity ID.
   */
  protected function invokeStorageLoadHook(array &$entities) {
    if (!empty($entities)) {
      // Call hook_entity_storage_load().
      foreach ($this->moduleHandler()->getImplementations('entity_storage_load') as $module) {
        $function = $module . '_entity_storage_load';
        $function($entities, $this->entityTypeId);
      }
      // Call hook_TYPE_storage_load().
      foreach ($this->moduleHandler()->getImplementations($this->entityTypeId . '_storage_load') as $module) {
        $function = $module . '_' . $this->entityTypeId . '_storage_load';
        $function($entities);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    switch ($hook) {
      case 'presave':
        $this->invokeFieldMethod('preSave', $entity);
        break;

      case 'insert':
        $this->invokeFieldPostSave($entity, FALSE);
        break;

      case 'update':
        $this->invokeFieldPostSave($entity, TRUE);
        break;
    }

    parent::invokeHook($hook, $entity);
  }

  /**
   * Invokes a method on the Field objects within an entity.
   *
   * Any argument passed will be forwarded to the invoked method.
   *
   * @param string $method
   *   The name of the method to be invoked.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   A multidimensional associative array of results, keyed by entity
   *   translation language code and field name.
   */
  protected function invokeFieldMethod($method, ContentEntityInterface $entity) {
    $result = [];
    $args = array_slice(func_get_args(), 2);
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $translation = $entity->getTranslation($langcode);
      // For non translatable fields, there is only one field object instance
      // across all translations and it has as parent entity the entity in the
      // default entity translation. Therefore field methods on non translatable
      // fields should be invoked only on the default entity translation.
      $fields = $translation->isDefaultTranslation() ? $translation->getFields() : $translation->getTranslatableFields();
      foreach ($fields as $name => $items) {
        // call_user_func_array() is way slower than a direct call so we avoid
        // using it if have no parameters.
        $result[$langcode][$name] = $args ? call_user_func_array([$items, $method], $args) : $items->{$method}();
      }
    }
    return $result;
  }

  /**
   * Invokes the post save method on the Field objects within an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param bool $update
   *   Specifies whether the entity is being updated or created.
   */
  protected function invokeFieldPostSave(ContentEntityInterface $entity, $update) {
    // For each entity translation this returns an array of resave flags keyed
    // by field name, thus we merge them to obtain a list of fields to resave.
    $resave = [];
    foreach ($this->invokeFieldMethod('postSave', $entity, $update) as $translation_results) {
      $resave += array_filter($translation_results);
    }
    if ($resave) {
      $this->doSaveFieldItems($entity, array_keys($resave));
    }
  }

  /**
   * Checks whether the field values changed compared to the original entity.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition of field to compare for changes.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to check for field changes.
   * @param \Drupal\Core\Entity\ContentEntityInterface $original
   *   Original entity to compare against.
   *
   * @return bool
   *   True if the field value changed from the original entity.
   */
  protected function hasFieldValueChanged(FieldDefinitionInterface $field_definition, ContentEntityInterface $entity, ContentEntityInterface $original) {
    $field_name = $field_definition->getName();
    $langcodes = array_keys($entity->getTranslationLanguages());
    if ($langcodes !== array_keys($original->getTranslationLanguages())) {
      // If the list of langcodes has changed, we need to save.
      return TRUE;
    }
    foreach ($langcodes as $langcode) {
      $items = $entity->getTranslation($langcode)->get($field_name)->filterEmptyItems();
      $original_items = $original->getTranslation($langcode)->get($field_name)->filterEmptyItems();
      // If the field items are not equal, we need to save.
      if (!$items->equals($original_items)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Populates the affected flag for all the revision translations.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity object being saved.
   */
  protected function populateAffectedRevisionTranslations(ContentEntityInterface $entity) {
    if ($this->entityType->isTranslatable() && $this->entityType->isRevisionable()) {
      $languages = $entity->getTranslationLanguages();
      foreach ($languages as $langcode => $language) {
        $translation = $entity->getTranslation($langcode);
        // Avoid populating the value if it was already manually set.
        $affected = $translation->isRevisionTranslationAffected();
        if (!isset($affected) && $translation->hasTranslationChanges()) {
          $translation->setRevisionTranslationAffected(TRUE);
        }
      }
    }
  }

  /**
   * Ensures integer entity IDs are valid.
   *
   * The identifier sanitization provided by this method has been introduced
   * as Drupal used to rely on the database to facilitate this, which worked
   * correctly with MySQL but led to errors with other DBMS such as PostgreSQL.
   *
   * @param array $ids
   *   The entity IDs to verify.
   *
   * @return array
   *   The sanitized list of entity IDs.
   */
  protected function cleanIds(array $ids) {
    $definitions = $this->entityManager->getBaseFieldDefinitions($this->entityTypeId);
    $id_definition = $definitions[$this->entityType->getKey('id')];
    if ($id_definition->getType() == 'integer') {
      $ids = array_filter($ids, function ($id) {
        return is_numeric($id) && $id == (int) $id;
      });
      $ids = array_map('intval', $ids);
    }
    return $ids;
  }

  /**
   * Gets entities from the persistent cache backend.
   *
   * @param array|null &$ids
   *   If not empty, return entities that match these IDs. IDs that were found
   *   will be removed from the list.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the persistent cache.
   */
  protected function getFromPersistentCache(array &$ids = NULL) {
    if (!$this->entityType->isPersistentlyCacheable() || empty($ids)) {
      return array();
    }
    $entities = array();
    // Build the list of cache entries to retrieve.
    $cid_map = array();
    foreach ($ids as $id) {
      $cid_map[$id] = $this->buildCacheId($id);
    }
    $cids = array_values($cid_map);
    if ($cache = $this->cacheBackend->getMultiple($cids)) {
      // Get the entities that were found in the cache.
      foreach ($ids as $index => $id) {
        $cid = $cid_map[$id];
        if (isset($cache[$cid])) {
          $entities[$id] = $cache[$cid]->data;
          unset($ids[$index]);
        }
      }
    }
    return $entities;
  }

  /**
   * Stores entities in the persistent cache backend.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Entities to store in the cache.
   */
  protected function setPersistentCache($entities) {
    if (!$this->entityType->isPersistentlyCacheable()) {
      return;
    }

    $cache_tags = array(
      $this->entityTypeId . '_values',
      'entity_field_info',
    );
    foreach ($entities as $id => $entity) {
      $this->cacheBackend->set($this->buildCacheId($id), $entity, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    if ($ids) {
      $cids = array();
      foreach ($ids as $id) {
        unset($this->entities[$id]);
        $cids[] = $this->buildCacheId($id);
      }
      if ($this->entityType->isPersistentlyCacheable()) {
        $this->cacheBackend->deleteMultiple($cids);
      }
    }
    else {
      $this->entities = array();
      if ($this->entityType->isPersistentlyCacheable()) {
        Cache::invalidateTags(array($this->entityTypeId . '_values'));
      }
    }
  }

  /**
   * Builds the cache ID for the passed in entity ID.
   *
   * @param int $id
   *   Entity ID for which the cache ID should be built.
   *
   * @return string
   *   Cache ID that can be passed to the cache backend.
   */
  protected function buildCacheId($id) {
    return "values:{$this->entityTypeId}:$id";
  }

}
