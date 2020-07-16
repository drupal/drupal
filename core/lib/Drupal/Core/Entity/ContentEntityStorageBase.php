<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TranslationStatusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for content entity storage handlers.
 */
abstract class ContentEntityStorageBase extends EntityStorageBase implements ContentEntityStorageInterface, DynamicallyFieldableEntityStorageInterface {

  /**
   * The entity bundle key.
   *
   * @var string|bool
   */
  protected $bundleKey = FALSE;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Stores the latest revision IDs for entities.
   *
   * @var array
   */
  protected $latestRevisionIds = [];

  /**
   * Constructs a ContentEntityStorageBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($entity_type, $memory_cache);
    $this->bundleKey = $this->entityType->getKey('bundle');
    $this->entityFieldManager = $entity_field_manager;
    $this->cacheBackend = $cache;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info')
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
        throw new EntityStorageException('Missing bundle for entity type ' . $this->entityTypeId);
      }

      // Normalize the bundle value. This is an optimized version of
      // \Drupal\Core\Field\FieldInputValueNormalizerTrait::normalizeValue()
      // because we just need the scalar value.
      $bundle_value = $values[$this->bundleKey];
      if (!is_array($bundle_value)) {
        // The bundle value is a scalar, use it as-is.
        $bundle = $bundle_value;
      }
      elseif (is_numeric(array_keys($bundle_value)[0])) {
        // The bundle value is a field item list array, keyed by delta.
        $bundle = reset($bundle_value[0]);
      }
      else {
        // The bundle value is a field item array, keyed by the field's main
        // property name.
        $bundle = reset($bundle_value);
      }
    }
    $entity = new $this->entityClass([], $this->entityTypeId, $bundle);
    $this->initFieldValues($entity, $values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function createWithSampleValues($bundle = FALSE, array $values = []) {
    // ID and revision should never have sample values generated for them.
    $forbidden_keys = [
      $this->entityType->getKey('id'),
    ];
    if ($revision_key = $this->entityType->getKey('revision')) {
      $forbidden_keys[] = $revision_key;
    }
    if ($bundle_key = $this->entityType->getKey('bundle')) {
      if (!$bundle) {
        throw new EntityStorageException("No entity bundle was specified");
      }
      if (!array_key_exists($bundle, $this->entityTypeBundleInfo->getBundleInfo($this->entityTypeId))) {
        throw new EntityStorageException(sprintf("Missing entity bundle. The \"%s\" bundle does not exist", $bundle));
      }
      $values[$bundle_key] = $bundle;
      // Bundle is already set
      $forbidden_keys[] = $bundle_key;
    }
    // Forbid sample generation on any keys whose values were submitted.
    $forbidden_keys = array_merge($forbidden_keys, array_keys($values));
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->create($values);
    foreach ($entity as $field_name => $value) {
      if (!in_array($field_name, $forbidden_keys, TRUE)) {
        $entity->get($field_name)->generateSampleItems();
      }
    }
    return $entity;
  }

  /**
   * Initializes field values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity object.
   * @param array $values
   *   (optional) An associative array of initial field values keyed by field
   *   name. If none is provided default values will be applied.
   * @param array $field_names
   *   (optional) An associative array of field names to be initialized. If none
   *   is provided all fields will be initialized.
   */
  protected function initFieldValues(ContentEntityInterface $entity, array $values = [], array $field_names = []) {
    // Populate field values.
    foreach ($entity as $name => $field) {
      if (!$field_names || isset($field_names[$name])) {
        if (isset($values[$name])) {
          $entity->$name = $values[$name];
        }
        elseif (!array_key_exists($name, $values)) {
          $entity->get($name)->applyDefaultValue();
        }
      }
      unset($values[$name]);
    }

    // Set any passed values for non-defined fields also.
    foreach ($values as $name => $value) {
      $entity->$name = $value;
    }

    // Make sure modules can alter field initial values.
    $this->invokeHook('field_values_init', $entity);
  }

  /**
   * Checks whether any entity revision is translated.
   *
   * @param \Drupal\Core\Entity\TranslatableInterface $entity
   *   The entity object to be checked.
   *
   * @return bool
   *   TRUE if the entity has at least one translation in any revision, FALSE
   *   otherwise.
   *
   * @see \Drupal\Core\TypedData\TranslatableInterface::getTranslationLanguages()
   * @see \Drupal\Core\Entity\ContentEntityStorageBase::isAnyStoredRevisionTranslated()
   */
  protected function isAnyRevisionTranslated(TranslatableInterface $entity) {
    return $entity->getTranslationLanguages(FALSE) || $this->isAnyStoredRevisionTranslated($entity);
  }

  /**
   * Checks whether any stored entity revision is translated.
   *
   * A revisionable entity can have translations in a pending revision, hence
   * the default revision may appear as not translated. This determines whether
   * the entity has any translation in the storage and thus should be considered
   * as multilingual.
   *
   * @param \Drupal\Core\Entity\TranslatableInterface $entity
   *   The entity object to be checked.
   *
   * @return bool
   *   TRUE if the entity has at least one translation in any revision, FALSE
   *   otherwise.
   *
   * @see \Drupal\Core\TypedData\TranslatableInterface::getTranslationLanguages()
   * @see \Drupal\Core\Entity\ContentEntityStorageBase::isAnyRevisionTranslated()
   */
  protected function isAnyStoredRevisionTranslated(TranslatableInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if ($entity->isNew()) {
      return FALSE;
    }

    if ($entity instanceof TranslationStatusInterface) {
      foreach ($entity->getTranslationLanguages(FALSE) as $langcode => $language) {
        if ($entity->getTranslationStatus($langcode) === TranslationStatusInterface::TRANSLATION_EXISTING) {
          return TRUE;
        }
      }
    }

    $query = $this->getQuery()
      ->condition($this->entityType->getKey('id'), $entity->id())
      ->condition($this->entityType->getKey('default_langcode'), 0)
      ->accessCheck(FALSE)
      ->range(0, 1);

    if ($entity->getEntityType()->isRevisionable()) {
      $query->allRevisions();
    }

    $result = $query->execute();
    return !empty($result);
  }

  /**
   * {@inheritdoc}
   */
  public function createTranslation(ContentEntityInterface $entity, $langcode, array $values = []) {
    $translation = $entity->getTranslation($langcode);
    $definitions = array_filter($translation->getFieldDefinitions(), function (FieldDefinitionInterface $definition) {
      return $definition->isTranslatable();
    });
    $field_names = array_map(function (FieldDefinitionInterface $definition) {
      return $definition->getName();
    }, $definitions);
    $values[$this->langcodeKey] = $langcode;
    $values[$this->getEntityType()->getKey('default_langcode')] = FALSE;
    $this->initFieldValues($translation, $values, $field_names);
    $this->invokeHook('translation_create', $translation);
    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function createRevision(RevisionableInterface $entity, $default = TRUE, $keep_untranslatable_fields = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $new_revision = clone $entity;

    $original_keep_untranslatable_fields = $keep_untranslatable_fields;

    // For translatable entities, create a merged revision of the active
    // translation and the other translations in the default revision. This
    // permits the creation of pending revisions that can always be saved as the
    // new default revision without reverting changes in other languages.
    if (!$entity->isNew() && !$entity->isDefaultRevision() && $entity->isTranslatable() && $this->isAnyRevisionTranslated($entity)) {
      $active_langcode = $entity->language()->getId();
      $skipped_field_names = array_flip($this->getRevisionTranslationMergeSkippedFieldNames());

      // By default we copy untranslatable field values from the default
      // revision, unless they are configured to affect only the default
      // translation. This way we can ensure we always have only one affected
      // translation in pending revisions. This constraint is enforced by
      // EntityUntranslatableFieldsConstraintValidator.
      if (!isset($keep_untranslatable_fields)) {
        $keep_untranslatable_fields = $entity->isDefaultTranslation() && $entity->isDefaultTranslationAffectedOnly();
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface $default_revision */
      $default_revision = $this->load($entity->id());
      $translation_languages = $default_revision->getTranslationLanguages();
      foreach ($translation_languages as $langcode => $language) {
        if ($langcode == $active_langcode) {
          continue;
        }

        $default_revision_translation = $default_revision->getTranslation($langcode);
        $new_revision_translation = $new_revision->hasTranslation($langcode) ?
          $new_revision->getTranslation($langcode) : $new_revision->addTranslation($langcode);

        /** @var \Drupal\Core\Field\FieldItemListInterface[] $sync_items */
        $sync_items = array_diff_key(
          $keep_untranslatable_fields ? $default_revision_translation->getTranslatableFields() : $default_revision_translation->getFields(),
          $skipped_field_names
        );
        foreach ($sync_items as $field_name => $items) {
          $new_revision_translation->set($field_name, $items->getValue());
        }

        // Make sure the "revision_translation_affected" flag is recalculated.
        $new_revision_translation->setRevisionTranslationAffected(NULL);

        // No need to copy untranslatable field values more than once.
        $keep_untranslatable_fields = TRUE;
      }

      // Make sure we do not inadvertently recreate removed translations.
      foreach (array_diff_key($new_revision->getTranslationLanguages(), $translation_languages) as $langcode => $language) {
        // Allow a new revision to be created for the active language.
        if ($langcode !== $active_langcode) {
          $new_revision->removeTranslation($langcode);
        }
      }

      // The "original" property is used in various places to detect changes in
      // field values with respect to the stored ones. If the property is not
      // defined, the stored version is loaded explicitly. Since the merged
      // revision generated here is not stored anywhere, we need to populate the
      // "original" property manually, so that changes can be properly detected.
      $new_revision->original = clone $new_revision;
    }

    // Eventually mark the new revision as such.
    $new_revision->setNewRevision();
    $new_revision->isDefaultRevision($default);

    // Actually make sure the current translation is marked as affected, even if
    // there are no explicit changes, to be sure this revision can be related
    // to the correct translation.
    $new_revision->setRevisionTranslationAffected(TRUE);

    // Notify modules about the new revision.
    $arguments = [$new_revision, $entity, $original_keep_untranslatable_fields];
    $this->moduleHandler()->invokeAll($this->entityTypeId . '_revision_create', $arguments);
    $this->moduleHandler()->invokeAll('entity_revision_create', $arguments);

    return $new_revision;
  }

  /**
   * Returns an array of field names to skip when merging revision translations.
   *
   * @return array
   *   An array of field names.
   */
  protected function getRevisionTranslationMergeSkippedFieldNames() {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->getEntityType();

    // A list of known revision metadata fields which should be skipped from
    // the comparison.
    $field_names = [
      $entity_type->getKey('revision'),
      $entity_type->getKey('revision_translation_affected'),
    ];
    $field_names = array_merge($field_names, array_values($entity_type->getRevisionMetadataKeys()));

    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId($entity_id) {
    if (!$this->entityType->isRevisionable()) {
      return NULL;
    }

    if (!isset($this->latestRevisionIds[$entity_id][LanguageInterface::LANGCODE_DEFAULT])) {
      $result = $this->getQuery()
        ->latestRevision()
        ->condition($this->entityType->getKey('id'), $entity_id)
        ->accessCheck(FALSE)
        ->execute();

      $this->latestRevisionIds[$entity_id][LanguageInterface::LANGCODE_DEFAULT] = key($result);
    }

    return $this->latestRevisionIds[$entity_id][LanguageInterface::LANGCODE_DEFAULT];
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestTranslationAffectedRevisionId($entity_id, $langcode) {
    if (!$this->entityType->isRevisionable()) {
      return NULL;
    }

    if (!$this->entityType->isTranslatable()) {
      return $this->getLatestRevisionId($entity_id);
    }

    if (!isset($this->latestRevisionIds[$entity_id][$langcode])) {
      $result = $this->getQuery()
        ->allRevisions()
        ->condition($this->entityType->getKey('id'), $entity_id)
        ->condition($this->entityType->getKey('revision_translation_affected'), 1, '=', $langcode)
        ->range(0, 1)
        ->sort($this->entityType->getKey('revision'), 'DESC')
        ->accessCheck(FALSE)
        ->execute();

      $this->latestRevisionIds[$entity_id][$langcode] = key($result);
    }
    return $this->latestRevisionIds[$entity_id][$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) {}

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
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {}

  /**
   * {@inheritdoc}
   */
  protected function preLoad(array &$ids = NULL) {
    $entities = [];

    // Call hook_entity_preload().
    $preload_ids = $ids ?: [];
    $preload_entities = $this->moduleHandler()->invokeAll('entity_preload', [$preload_ids, $this->entityTypeId]);
    foreach ((array) $preload_entities as $entity) {
      $entities[$entity->id()] = $entity;
    }

    if ($entities) {
      // If any entities were pre-loaded, remove them from the IDs still to
      // load.
      if ($ids !== NULL) {
        $ids = array_keys(array_diff_key(array_flip($ids), $entities));
      }
      // If we had to load all the entities ($ids was set to NULL), get an array
      // of IDs that still need to be loaded.
      else {
        $result = $this->getQuery()
          ->accessCheck(FALSE)
          ->condition($this->entityType->getKey('id'), array_keys($entities), 'NOT IN')
          ->execute();
        $ids = array_values($result);
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    $revisions = $this->loadMultipleRevisions([$revision_id]);

    return isset($revisions[$revision_id]) ? $revisions[$revision_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleRevisions(array $revision_ids) {
    $revisions = $this->doLoadMultipleRevisionsFieldItems($revision_ids);

    // The hooks are executed with an array of entities keyed by the entity ID.
    // As we could load multiple revisions for the same entity ID at once we
    // have to build groups of entities where the same entity ID is present only
    // once.
    $entity_groups = [];
    $entity_group_mapping = [];
    foreach ($revisions as $revision) {
      $entity_id = $revision->id();
      $entity_group_key = isset($entity_group_mapping[$entity_id]) ? $entity_group_mapping[$entity_id] + 1 : 0;
      $entity_group_mapping[$entity_id] = $entity_group_key;
      $entity_groups[$entity_group_key][$entity_id] = $revision;
    }

    // Invoke the entity hooks for each group.
    foreach ($entity_groups as $entities) {
      $this->invokeStorageLoadHook($entities);
      $this->postLoad($entities);
    }

    // Ensure that the returned array is ordered the same as the original
    // $ids array if this was passed in and remove any invalid IDs.
    if ($revision_ids) {
      $flipped_ids = array_intersect_key(array_flip($revision_ids), $revisions);
      $revisions = array_replace($flipped_ids, $revisions);
    }

    return $revisions;
  }

  /**
   * Actually loads revision field item values from the storage.
   *
   * @param array $revision_ids
   *   An array of revision identifiers.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The specified entity revisions or an empty array if none are found.
   */
  abstract protected function doLoadMultipleRevisionsFieldItems($revision_ids);

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

    // Populate the "revision_default" flag. We skip this when we are resaving
    // the revision because this is only allowed for default revisions, and
    // these cannot be made non-default.
    if ($this->entityType->isRevisionable() && $entity->isNewRevision()) {
      $revision_default_key = $this->entityType->getRevisionMetadataKey('revision_default');
      $entity->set($revision_default_key, $entity->isDefaultRevision());
    }

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

    if ($entity->getEntityType()->isRevisionable() && !$entity->isNew() && empty($entity->getLoadedRevisionId())) {
      // Update the loaded revision id for rare special cases when no loaded
      // revision is given when updating an existing entity. This for example
      // happens when calling save() in hook_entity_insert().
      $entity->updateLoadedRevisionId();
    }

    $id = parent::doPreSave($entity);

    if (!$entity->isNew()) {
      // If the ID changed then original can't be loaded, throw an exception
      // in that case.
      if (empty($entity->original) || $entity->id() != $entity->original->id()) {
        throw new EntityStorageException("Update existing '{$this->entityTypeId}' entity while changing the ID is not supported.");
      }
      // Do not allow changing the revision ID when resaving the current
      // revision.
      if (!$entity->isNewRevision() && $entity->getRevisionId() != $entity->getLoadedRevisionId()) {
        throw new EntityStorageException("Update existing '{$this->entityTypeId}' entity revision while changing the revision ID is not supported.");
      }
    }

    return $id;
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
      $entity->updateLoadedRevisionId();
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
        $this->invokeHook('translation_delete', $entity->original->getTranslation($langcode));
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
    $langcodes = array_keys($entity->getTranslationLanguages());
    // Ensure that the field method is invoked as first on the current entity
    // translation and then on all other translations.
    $current_entity_langcode = $entity->language()->getId();
    if (reset($langcodes) != $current_entity_langcode) {
      $langcodes = array_diff($langcodes, [$current_entity_langcode]);
      array_unshift($langcodes, $current_entity_langcode);
    }
    foreach ($langcodes as $langcode) {
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

    // We need to call the delete method for field items of removed
    // translations.
    if ($method == 'postSave' && !empty($entity->original)) {
      $original_langcodes = array_keys($entity->original->getTranslationLanguages());
      foreach (array_diff($original_langcodes, $langcodes) as $removed_langcode) {
        $translation = $entity->original->getTranslation($removed_langcode);
        $fields = $translation->getTranslatableFields();
        foreach ($fields as $name => $items) {
          $items->delete();
        }
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
        $current_affected = $translation->isRevisionTranslationAffected();
        if (!isset($current_affected) || ($entity->isNewRevision() && !$translation->isRevisionTranslationAffectedEnforced())) {
          // When setting the revision translation affected flag we have to
          // explicitly set it to not be enforced. By default it will be
          // enforced automatically when being set, which allows us to determine
          // if the flag has been already set outside the storage in which case
          // we should not recompute it.
          // @see \Drupal\Core\Entity\ContentEntityBase::setRevisionTranslationAffected().
          $new_affected = $translation->hasTranslationChanges() ? TRUE : NULL;
          $translation->setRevisionTranslationAffected($new_affected);
          $translation->setRevisionTranslationAffectedEnforced(FALSE);
        }
      }
    }
  }

  /**
   * Ensures integer entity key values are valid.
   *
   * The identifier sanitization provided by this method has been introduced
   * as Drupal used to rely on the database to facilitate this, which worked
   * correctly with MySQL but led to errors with other DBMS such as PostgreSQL.
   *
   * @param array $ids
   *   The entity key values to verify.
   * @param string $entity_key
   *   (optional) The entity key to sanitize values for. Defaults to 'id'.
   *
   * @return array
   *   The sanitized list of entity key values.
   */
  protected function cleanIds(array $ids, $entity_key = 'id') {
    $definitions = $this->entityFieldManager->getActiveFieldStorageDefinitions($this->entityTypeId);
    $field_name = $this->entityType->getKey($entity_key);
    if ($field_name && $definitions[$field_name]->getType() == 'integer') {
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
      return [];
    }
    $entities = [];
    // Build the list of cache entries to retrieve.
    $cid_map = [];
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

    $cache_tags = [
      $this->entityTypeId . '_values',
      'entity_field_info',
    ];
    foreach ($entities as $id => $entity) {
      $this->cacheBackend->set($this->buildCacheId($id), $entity, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadUnchanged($id) {
    $entities = [];
    $ids = [$id];

    // The cache invalidation in the parent has the side effect that loading the
    // same entity again during the save process (for example in
    // hook_entity_presave()) will load the unchanged entity. Simulate this
    // by explicitly removing the entity from the static cache.
    parent::resetCache($ids);

    // Gather entities from a 'preload' hook. This hook can be used by modules
    // that need, for example, to return a different revision than the default
    // one for revisionable entity types.
    $preloaded_entities = $this->preLoad($ids);
    if (!empty($preloaded_entities)) {
      $entities += $preloaded_entities;
    }

    // The default implementation in the parent class unsets the current cache
    // and then reloads the entity. That is slow, especially if this is done
    // repeatedly in the same request, e.g. when validating and then saving
    // an entity. Optimize this for content entities by trying to load them
    // directly from the persistent cache again, as in contrast to the static
    // cache the persistent one will never be changed until the entity is saved.
    $entities += $this->getFromPersistentCache($ids);

    if (!$entities) {
      $entities[$id] = $this->load($id);
    }
    else {
      // As the entities are put into the persistent cache before the post load
      // has been executed we have to execute it if we have retrieved the
      // entity directly from the persistent cache.
      $this->postLoad($entities);

      // As we've removed the entity from the static cache already we have to
      // put the loaded unchanged entity there to simulate the behavior of the
      // parent.
      $this->setStaticCache($entities);
    }

    return $entities[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    if ($ids) {
      parent::resetCache($ids);
      if ($this->entityType->isPersistentlyCacheable()) {
        $cids = [];
        foreach ($ids as $id) {
          unset($this->latestRevisionIds[$id]);
          $cids[] = $this->buildCacheId($id);
        }
        $this->cacheBackend->deleteMultiple($cids);
      }
    }
    else {
      parent::resetCache();
      if ($this->entityType->isPersistentlyCacheable()) {
        Cache::invalidateTags([$this->entityTypeId . '_values']);
      }
      $this->latestRevisionIds = [];
    }
  }

}
