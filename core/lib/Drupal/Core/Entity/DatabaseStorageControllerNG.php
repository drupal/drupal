<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\DatabaseStorageControllerNG.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use PDO;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Database\Connection;

/**
 * Implements Field API specific enhancements to the DatabaseStorageController class.
 *
 * @todo: Once all entity types have been converted, merge improvements into the
 * DatabaseStorageController class.
 *
 * See the EntityNG documentation for an explanation of "NG".
 *
 * @see \Drupal\Core\EntityNG
 */
class DatabaseStorageControllerNG extends DatabaseStorageController {

  /**
   * The entity class to use.
   *
   * @var string
   */
  protected $entityClass;

  /**
   * The entity bundle key.
   *
   * @var string|bool
   */
  protected $bundleKey;

  /**
   * The table that stores properties, if the entity has multilingual support.
   *
   * @var string
   */
  protected $dataTable;

  /**
   * Overrides DatabaseStorageController::__construct().
   */
  public function __construct($entity_type, array $entity_info, Connection $database) {
    parent::__construct($entity_type,$entity_info, $database);
    $this->bundleKey = !empty($this->entityInfo['entity_keys']['bundle']) ? $this->entityInfo['entity_keys']['bundle'] : FALSE;
    $this->entityClass = $this->entityInfo['class'];

    // Check if the entity type has a dedicated table for properties.
    if (!empty($this->entityInfo['data_table'])) {
      $this->dataTable = $this->entityInfo['data_table'];
    }

    // Work-a-round to let load() get stdClass storage records without having to
    // override it. We map storage records to entities in
    // DatabaseStorageControllerNG:: mapFromStorageRecords().
    // @todo: Remove this once this is moved in the main controller.
    unset($this->entityInfo['class']);
  }

  /**
   * Overrides DatabaseStorageController::create().
   *
   * @param array $values
   *   An array of values to set, keyed by field name. The value has to be
   *   the plain value of an entity field, i.e. an array of field items.
   *   If no numerically indexed array is given, the value will be set for the
   *   first field item. For example, to set the first item of a 'name'
   *   field one can pass:
   *   @code
   *     $values = array('name' => array(0 => array('value' => 'the name')));
   *   @endcode
   *   or
   *   @code
   *     $values = array('name' => array('value' => 'the name'));
   *   @endcode
   *   If the 'name' field is a defined as 'string_item' which supports
   *   setting its value by a string, it's also possible to just pass the name
   *   string:
   *   @code
   *     $values = array('name' => 'the name');
   *   @endcode
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function create(array $values) {
    // We have to determine the bundle first.
    $bundle = FALSE;
    if ($this->bundleKey) {
      if (!isset($values[$this->bundleKey])) {
        throw new EntityStorageException(t('Missing bundle for entity type @type', array('@type' => $this->entityType)));
      }
      $bundle = $values[$this->bundleKey];
    }
    $entity = new $this->entityClass(array(), $this->entityType, $bundle);

    // Set all other given values.
    foreach ($values as $name => $value) {
      $entity->$name = $value;
    }

    // Assign a new UUID if there is none yet.
    if ($this->uuidKey && !isset($entity->{$this->uuidKey}->value)) {
      $uuid = new Uuid();
      $entity->{$this->uuidKey} = $uuid->generate();
    }

    // Modules might need to add or change the data initially held by the new
    // entity object, for instance to fill-in default values.
    $this->invokeHook('create', $entity);

    return $entity;
  }

  /**
   * Builds an entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $entity_query
   *   EntityQuery instance.
   * @param array $values
   *   An associative array of properties of the entity, where the keys are the
   *   property names and the values are the values those properties must have.
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    if ($this->dataTable) {
      // @todo We should not be using a condition to specify whether conditions
      //   apply to the default language. See http://drupal.org/node/1866330.
      // Default to the original entity language if not explicitly specified
      // otherwise.
      if (!array_key_exists('default_langcode', $values)) {
        $values['default_langcode'] = 1;
      }
      // If the 'default_langcode' flag is explicitly not set, we do not care
      // whether the queried values are in the original entity language or not.
      elseif ($values['default_langcode'] === NULL) {
        unset($values['default_langcode']);
      }
    }

    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = $this->database->select($this->entityInfo['base_table'], 'base');
    $is_revision_query = $this->revisionKey && ($revision_id || !$this->dataTable);

    $query->addTag($this->entityType . '_load_multiple');

    if ($revision_id) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->idKey} = base.{$this->idKey} AND revision.{$this->revisionKey} = :revisionId", array(':revisionId' => $revision_id));
    }
    elseif ($is_revision_query) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->revisionKey} = base.{$this->revisionKey}");
    }

    // Add fields from the {entity} table.
    $entity_fields = drupal_schema_fields_sql($this->entityInfo['base_table']);

    if ($is_revision_query) {
      // Add all fields from the {entity_revision} table.
      $entity_revision_fields = drupal_map_assoc(drupal_schema_fields_sql($this->entityInfo['revision_table']));
      // The ID field is provided by entity, so remove it.
      unset($entity_revision_fields[$this->idKey]);

      // Remove all fields from the base table that are also fields by the same
      // name in the revision table.
      $entity_field_keys = array_flip($entity_fields);
      foreach ($entity_revision_fields as $key => $name) {
        if (isset($entity_field_keys[$name])) {
          unset($entity_fields[$entity_field_keys[$name]]);
        }
      }
      $query->fields('revision', $entity_revision_fields);

      // Compare revision ID of the base and revision table, if equal then this
      // is the default revision.
      $query->addExpression('base.' . $this->revisionKey . ' = revision.' . $this->revisionKey, 'isDefaultRevision');
    }

    $query->fields('base', $entity_fields);

    if ($ids) {
      $query->condition("base.{$this->idKey}", $ids, 'IN');
    }

    return $query;
  }

  /**
   * Overrides DatabaseStorageController::attachLoad().
   *
   * Added mapping from storage records to entities.
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    // Map the loaded stdclass records into entity objects and according fields.
    $queried_entities = $this->mapFromStorageRecords($queried_entities, $load_revision);

    if ($this->entityInfo['fieldable']) {
      if ($load_revision) {
        field_attach_load_revision($this->entityType, $queried_entities);
      }
      else {
        field_attach_load($this->entityType, $queried_entities);
      }
    }

    // Call hook_entity_load().
    foreach (module_implements('entity_load') as $module) {
      $function = $module . '_entity_load';
      $function($queried_entities, $this->entityType);
    }
    // Call hook_TYPE_load(). The first argument for hook_TYPE_load() are
    // always the queried entities, followed by additional arguments set in
    // $this->hookLoadArguments.
    $args = array_merge(array($queried_entities), $this->hookLoadArguments);
    foreach (module_implements($this->entityType . '_load') as $module) {
      call_user_func_array($module . '_' . $this->entityType . '_load', $args);
    }
  }

  /**
   * Maps from storage records to entity objects.
   *
   * @param array $records
   *   Associative array of query results, keyed on the entity ID.
   * @param boolean $load_revision
   *   (optional) TRUE if the revision should be loaded, defaults to FALSE.
   *
   * @return array
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records, $load_revision = FALSE) {
    $entities = array();
    foreach ($records as $id => $record) {
      $entities[$id] = array();
      foreach ($record as $name => $value) {
        // Skip the item delta and item value levels but let the field assign
        // the value as suiting. This avoids unnecessary array hierarchies and
        // saves memory here.
        $entities[$id][$name][Language::LANGCODE_DEFAULT] = $value;
      }
      // If we have no multilingual values we can instantiate entity objecs
      // right now, otherwise we need to collect all the field values first.
      if (!$this->dataTable) {
        $bundle = $this->bundleKey ? $record->{$this->bundleKey} : FALSE;
        // Turn the record into an entity class.
        $entities[$id] = new $this->entityClass($entities[$id], $this->entityType, $bundle);
      }
    }
    $this->attachPropertyData($entities, $load_revision);
    return $entities;
  }

  /**
   * Attaches property data in all languages for translatable properties.
   *
   * @param array &$entities
   *   Associative array of entities, keyed on the entity ID.
   * @param int $revision_id
   *   (optional) The revision to be loaded. Defaults to FALSE.
   */
  protected function attachPropertyData(array &$entities, $revision_id = FALSE) {
    if ($this->dataTable) {
      // If a revision table is available, we need all the properties of the
      // latest revision. Otherwise we fall back to the data table.
      $table = $this->revisionTable ?: $this->dataTable;
      $query = $this->database->select($table, 'data', array('fetch' => PDO::FETCH_ASSOC))
        ->fields('data')
        ->condition($this->idKey, array_keys($entities))
        ->orderBy('data.' . $this->idKey);

      if ($this->revisionTable) {
        if ($revision_id) {
          $query->condition($this->revisionKey, $revision_id);
        }
        else {
          // Get the revision IDs.
          $revision_ids = array();
          foreach ($entities as $id => $values) {
            $revision_ids[] = $values[$this->revisionKey];
          }
          $query->condition($this->revisionKey, $revision_ids);
        }
      }

      $data = $query->execute();
      $field_definition = $this->getFieldDefinitions(array());
      if ($this->revisionTable) {
        $data_fields = array_flip(array_diff(drupal_schema_fields_sql($this->entityInfo['revision_table']), drupal_schema_fields_sql($this->entityInfo['base_table'])));
      }
      else {
        $data_fields = array_flip(drupal_schema_fields_sql($this->entityInfo['data_table']));
      }

      foreach ($data as $values) {
        $id = $values[$this->idKey];

        // Field values in default language are stored with
        // Language::LANGCODE_DEFAULT as key.
        $langcode = empty($values['default_langcode']) ? $values['langcode'] : Language::LANGCODE_DEFAULT;

        foreach ($field_definition as $name => $definition) {
          // Set only translatable properties, unless we are dealing with a
          // revisable entity, in which case we did not load the untranslatable
          // data before.
          $translatable = !empty($definition['translatable']);
          if (isset($data_fields[$name]) && ($this->revisionTable || $translatable)) {
            $entities[$id][$name][$langcode] = $values[$name];
          }
        }
      }

      foreach ($entities as $id => $values) {
        $bundle = $this->bundleKey ? $values[$this->bundleKey][Language::LANGCODE_DEFAULT] : FALSE;
        // Turn the record into an entity class.
        $entities[$id] = new $this->entityClass($values, $this->entityType, $bundle);
      }
    }
  }

  /**
   * Overrides DatabaseStorageController::save().
   *
   * Added mapping from entities to storage records before saving.
   */
  public function save(EntityInterface $entity) {
    $transaction = $this->database->startTransaction();
    try {
      // Ensure we are dealing with the actual entity.
      $entity = $entity->getNGEntity();

      // Sync the changes made in the fields array to the internal values array.
      $entity->updateOriginalValues();

      // Load the stored entity, if any.
      if (!$entity->isNew() && !isset($entity->original)) {
        $entity->original = entity_load_unchanged($this->entityType, $entity->id());
      }

      $this->preSave($entity);
      $this->invokeHook('presave', $entity);

      // Create the storage record to be saved.
      $record = $this->mapToStorageRecord($entity);

      if (!$entity->isNew()) {
        if ($entity->isDefaultRevision()) {
          $return = drupal_write_record($this->entityInfo['base_table'], $record, $this->idKey);
        }
        else {
          // @todo, should a different value be returned when saving an entity
          // with $isDefaultRevision = FALSE?
          $return = FALSE;
        }
        if ($this->revisionKey) {
          $record->{$this->revisionKey} = $this->saveRevision($entity);
        }
        if ($this->dataTable) {
          $this->savePropertyData($entity);
        }
        $this->resetCache(array($entity->id()));
        $this->postSave($entity, TRUE);
        $this->invokeHook('update', $entity);
      }
      else {
        $return = drupal_write_record($this->entityInfo['base_table'], $record);
        $entity->{$this->idKey}->value = $record->{$this->idKey};
        if ($this->revisionKey) {
          $record->{$this->revisionKey} = $this->saveRevision($entity);
        }
        $entity->{$this->idKey}->value = $record->{$this->idKey};
        if ($this->dataTable) {
          $this->savePropertyData($entity);
        }

        // Reset general caches, but keep caches specific to certain entities.
        $this->resetCache(array());

        $entity->enforceIsNew(FALSE);
        $this->postSave($entity, FALSE);
        $this->invokeHook('insert', $entity);
      }

      // Ignore slave server temporarily.
      db_ignore_slave();
      unset($entity->original);

      return $return;
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Saves an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return integer
   *   The revision id.
   */
  protected function saveRevision(EntityInterface $entity) {
    $return = $entity->id();
    $default_langcode = $entity->language()->langcode;

    if (!$entity->isNewRevision()) {
      // Delete to handle removed values.
      $this->database->delete($this->revisionTable)
        ->condition($this->idKey, $entity->id())
        ->condition($this->revisionKey, $entity->getRevisionId())
        ->execute();
    }

    $languages = $this->dataTable ? $entity->getTranslationLanguages(TRUE) : array($default_langcode => $entity->language());
    foreach ($languages as $langcode => $language) {
      $translation = $entity->getTranslation($langcode, FALSE);
      $record = $this->mapToRevisionStorageRecord($translation);
      $record->langcode = $langcode;
      $record->default_langcode = $langcode == $default_langcode;

      // When saving a new revision, set any existing revision ID to NULL so as
      // to ensure that a new revision will actually be created.
      if ($entity->isNewRevision() && isset($record->{$this->revisionKey})) {
        $record->{$this->revisionKey} = NULL;
      }

      $this->preSaveRevision($record, $entity);

      if ($entity->isNewRevision()) {
        drupal_write_record($this->revisionTable, $record);
        if ($entity->isDefaultRevision()) {
          $this->database->update($this->entityInfo['base_table'])
            ->fields(array($this->revisionKey => $record->{$this->revisionKey}))
            ->condition($this->idKey, $record->{$this->idKey})
            ->execute();
        }
        $entity->setNewRevision(FALSE);
      }
      else {
        // @todo Use multiple insertions to improve performance.
        drupal_write_record($this->revisionTable, $record);
      }

      // Make sure to update the new revision key for the entity.
      $entity->{$this->revisionKey}->value = $record->{$this->revisionKey};
      $return = $record->{$this->revisionKey};
    }

    return $return;
  }

  /**
   * Stores the entity property language-aware data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  protected function savePropertyData(EntityInterface $entity) {
    // Delete and insert to handle removed values.
    $this->database->delete($this->dataTable)
      ->condition($this->idKey, $entity->id())
      ->execute();

    $query = $this->database->insert($this->dataTable);

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $record = $this->mapToDataStorageRecord($entity, $langcode);
      $values = (array) $record;
      $query
        ->fields(array_keys($values))
        ->values($values);
    }

    $query->execute();
  }

  /**
   * Overrides DatabaseStorageController::invokeHook().
   *
   * Invokes field API attachers with a BC entity.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    $function = 'field_attach_' . $hook;
    // @todo: field_attach_delete_revision() is named the wrong way round,
    // consider renaming it.
    if ($function == 'field_attach_revision_delete') {
      $function = 'field_attach_delete_revision';
    }
    if (!empty($this->entityInfo['fieldable']) && function_exists($function)) {
      $function($entity);
    }

    // Invoke the hook.
    module_invoke_all($this->entityType . '_' . $hook, $entity);
    // Invoke the respective entity-level hook.
    module_invoke_all('entity_' . $hook, $entity, $this->entityType);
  }

  /**
   * Maps from an entity object to the storage record of the base table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \stdClass
   *   The record to store.
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    $record = new \stdClass();
    foreach (drupal_schema_fields_sql($this->entityInfo['base_table']) as $name) {
      $record->$name = $entity->$name->value;
    }
    return $record;
  }

  /**
   * Maps from an entity object to the storage record of the revision table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \stdClass
   *   The record to store.
   */
  protected function mapToRevisionStorageRecord(ComplexDataInterface $entity) {
    $record = new \stdClass();
    $definitions = $entity->getPropertyDefinitions();
    foreach (drupal_schema_fields_sql($this->entityInfo['revision_table']) as $name) {
      if (isset($definitions[$name]) && isset($entity->$name->value)) {
        $record->$name = $entity->$name->value;
      }
    }
    return $record;
  }

  /**
   * Maps from an entity object to the storage record of the data table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param $langcode
   *   The language code of the translation to get.
   *
   * @return \stdClass
   *   The record to store.
   */
  protected function mapToDataStorageRecord(EntityInterface $entity, $langcode) {
    $default_langcode = $entity->language()->langcode;
    // Don't use strict mode, this way there's no need to do checks here, as
    // non-translatable properties are replicated for each language.
    $translation = $entity->getTranslation($langcode, FALSE);
    $definitions = $translation->getPropertyDefinitions();
    $schema = drupal_get_schema($this->entityInfo['data_table']);

    $record = new \stdClass();
    foreach (drupal_schema_fields_sql($this->entityInfo['data_table']) as $name) {
      $info = $schema['fields'][$name];
      $value = isset($definitions[$name]) && isset($translation->$name->value) ? $translation->$name->value : NULL;
      $record->$name = drupal_schema_get_field_value($info, $value);
    }
    $record->langcode = $langcode;
    $record->default_langcode = intval($default_langcode == $langcode);

    return $record;
  }

  /**
   * Overwrites \Drupal\Core\Entity\DatabaseStorageController::delete().
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }

    $transaction = $this->database->startTransaction();
    try {
      // Ensure we are dealing with the actual entities.
      foreach ($entities as $id => $entity) {
        $entities[$id] = $entity->getNGEntity();
      }

      $this->preDelete($entities);
      foreach ($entities as $id => $entity) {
        $this->invokeHook('predelete', $entity);
      }
      $ids = array_keys($entities);

      $this->database->delete($this->entityInfo['base_table'])
        ->condition($this->idKey, $ids)
        ->execute();

      if ($this->revisionKey) {
        $this->database->delete($this->revisionTable)
          ->condition($this->idKey, $ids)
          ->execute();
      }

      if ($this->dataTable) {
        $this->database->delete($this->dataTable)
          ->condition($this->idKey, $ids)
          ->execute();
      }

      // Reset the cache as soon as the changes have been applied.
      $this->resetCache($ids);

      $this->postDelete($entities);
      foreach ($entities as $id => $entity) {
        $this->invokeHook('delete', $entity);
      }
      // Ignore slave server temporarily.
      db_ignore_slave();
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }
}
