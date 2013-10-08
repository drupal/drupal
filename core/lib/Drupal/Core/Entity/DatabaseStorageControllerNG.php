<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\DatabaseStorageControllerNG.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use Drupal\field\FieldInfo;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;

/**
 * Implements Field API specific enhancements to the DatabaseStorageController class.
 *
 * @todo: Once all entity types have been converted, merge improvements into the
 * DatabaseStorageController class.
 *
 * @see \Drupal\Core\ContentEntityBase
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
   * The table that stores revision field data if the entity supports revisions.
   *
   * @var string
   */
  protected $revisionDataTable;

  /**
   * Overrides DatabaseStorageController::__construct().
   */
  public function __construct($entity_type, array $entity_info, Connection $database, FieldInfo $field_info, UuidInterface $uuid_service) {
    parent::__construct($entity_type,$entity_info, $database, $field_info, $uuid_service);
    $this->bundleKey = !empty($this->entityInfo['entity_keys']['bundle']) ? $this->entityInfo['entity_keys']['bundle'] : FALSE;
    $this->entityClass = $this->entityInfo['class'];

    // Check if the entity type has a dedicated table for properties.
    if (!empty($this->entityInfo['data_table'])) {
      $this->dataTable = $this->entityInfo['data_table'];
      // Entity types having both revision and translation support should always
      // define a revision data table.
      if ($this->revisionTable && !empty($this->entityInfo['revision_data_table'])) {
        $this->revisionDataTable = $this->entityInfo['revision_data_table'];
      }
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
    $entity_class = $this->entityClass;
    $entity_class::preCreate($this, $values);

    // We have to determine the bundle first.
    $bundle = FALSE;
    if ($this->bundleKey) {
      if (!isset($values[$this->bundleKey])) {
        throw new EntityStorageException(format_string('Missing bundle for entity type @type', array('@type' => $this->entityType)));
      }
      $bundle = $values[$this->bundleKey];
    }
    $entity = new $this->entityClass(array(), $this->entityType, $bundle);

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
    $entity->postCreate($this);

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
   * Overrides DatabaseStorageController::attachLoad().
   *
   * Added mapping from storage records to entities.
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    // Map the loaded stdclass records into entity objects and according fields.
    $queried_entities = $this->mapFromStorageRecords($queried_entities, $load_revision);
    parent::attachLoad($queried_entities, $load_revision);
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
      $table = $this->revisionDataTable ?: $this->dataTable;
      $query = $this->database->select($table, 'data', array('fetch' => \PDO::FETCH_ASSOC))
        ->fields('data')
        ->condition($this->idKey, array_keys($entities))
        ->orderBy('data.' . $this->idKey);

      if ($this->revisionDataTable) {
        if ($revision_id) {
          $query->condition($this->revisionKey, $revision_id);
        }
        else {
          // Get the revision IDs.
          $revision_ids = array();
          foreach ($entities as $values) {
            $revision_ids[] = $values[$this->revisionKey];
          }
          $query->condition($this->revisionKey, $revision_ids);
        }
      }

      $data = $query->execute();
      $field_definition = \Drupal::entityManager()->getFieldDefinitions($this->entityType);
      $translations = array();
      if ($this->revisionDataTable) {
        $data_fields = array_flip(array_diff(drupal_schema_fields_sql($this->entityInfo['revision_data_table']), drupal_schema_fields_sql($this->entityInfo['base_table'])));
      }
      else {
        $data_fields = array_flip(drupal_schema_fields_sql($this->entityInfo['data_table']));
      }

      foreach ($data as $values) {
        $id = $values[$this->idKey];

        // Field values in default language are stored with
        // Language::LANGCODE_DEFAULT as key.
        $langcode = empty($values['default_langcode']) ? $values['langcode'] : Language::LANGCODE_DEFAULT;
        $translations[$id][$langcode] = TRUE;

        foreach ($field_definition as $name => $definition) {
          if (isset($data_fields[$name])) {
            $entities[$id][$name][$langcode] = $values[$name];
          }
        }
      }

      foreach ($entities as $id => $values) {
        $bundle = $this->bundleKey ? $values[$this->bundleKey][Language::LANGCODE_DEFAULT] : FALSE;
        // Turn the record into an entity class.
        $entities[$id] = new $this->entityClass($values, $this->entityType, $bundle, array_keys($translations[$id]));
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
      // Sync the changes made in the fields array to the internal values array.
      $entity->updateOriginalValues();

      // Load the stored entity, if any.
      if (!$entity->isNew() && !isset($entity->original)) {
        $entity->original = entity_load_unchanged($this->entityType, $entity->id());
      }

      $entity->preSave($this);
      $this->invokeFieldMethod('preSave', $entity);
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
        if ($this->revisionDataTable) {
          $this->savePropertyData($entity, 'revision_data_table');
        }
        $this->resetCache(array($entity->id()));
        $entity->setNewRevision(FALSE);
        $entity->postSave($this, TRUE);
        $this->invokeFieldMethod('update', $entity);
        $this->saveFieldItems($entity, TRUE);
        $this->invokeHook('update', $entity);
        if ($this->dataTable) {
          $this->invokeTranslationHooks($entity);
        }
      }
      else {
        // Ensure the entity is still seen as new after assigning it an id,
        // while storing its data.
        $entity->enforceIsNew();
        $return = drupal_write_record($this->entityInfo['base_table'], $record);
        $entity->{$this->idKey}->value = (string) $record->{$this->idKey};
        if ($this->revisionKey) {
          $entity->setNewRevision();
          $record->{$this->revisionKey} = $this->saveRevision($entity);
        }
        if ($this->dataTable) {
          $this->savePropertyData($entity);
        }
        if ($this->revisionDataTable) {
          $this->savePropertyData($entity, 'revision_data_table');
        }

        // Reset general caches, but keep caches specific to certain entities.
        $this->resetCache(array());

        $entity->enforceIsNew(FALSE);
        $entity->postSave($this, FALSE);
        $this->invokeFieldMethod('insert', $entity);
        $this->saveFieldItems($entity, FALSE);
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
    $record = $this->mapToStorageRecord($entity, 'revision_table');

    // When saving a new revision, set any existing revision ID to NULL so as
    // to ensure that a new revision will actually be created.
    if ($entity->isNewRevision() && isset($record->{$this->revisionKey})) {
      $record->{$this->revisionKey} = NULL;
    }

    $entity->preSaveRevision($this, $record);

    if ($entity->isNewRevision()) {
      drupal_write_record($this->revisionTable, $record);
      if ($entity->isDefaultRevision()) {
        $this->database->update($this->entityInfo['base_table'])
          ->fields(array($this->revisionKey => $record->{$this->revisionKey}))
          ->condition($this->idKey, $record->{$this->idKey})
          ->execute();
      }
    }
    else {
      drupal_write_record($this->revisionTable, $record, array($this->revisionKey));
    }

    // Make sure to update the new revision key for the entity.
    $entity->{$this->revisionKey}->value = $record->{$this->revisionKey};

    return $record->{$this->revisionKey};
  }

  /**
   * Stores the entity property language-aware data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $table_key
   *   (optional) The entity key identifying the target table. Defaults to
   *   'data_table'.
   */
  protected function savePropertyData(EntityInterface $entity, $table_key = 'data_table') {
    $table_name = $this->entityInfo[$table_key];
    $revision = $table_key != 'data_table';

    if (!$revision || !$entity->isNewRevision()) {
      $key = $revision ? $this->revisionKey : $this->idKey;
      $value = $revision ? $entity->getRevisionId() : $entity->id();
      // Delete and insert to handle removed values.
      $this->database->delete($table_name)
        ->condition($key, $value)
        ->execute();
    }

    $query = $this->database->insert($table_name);

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      $record = $this->mapToDataStorageRecord($translation, $table_key);
      $values = (array) $record;
      $query
        ->fields(array_keys($values))
        ->values($values);
    }

    $query->execute();
  }

  /**
   * Maps from an entity object to the storage record.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $table_key
   *   (optional) The entity key identifying the target table. Defaults to
   *   'base_table'.
   *
   * @return \stdClass
   *   The record to store.
   */
  protected function mapToStorageRecord(EntityInterface $entity, $table_key = 'base_table') {
    $record = new \stdClass();
    $definitions = $entity->getPropertyDefinitions();
    $schema = drupal_get_schema($this->entityInfo[$table_key]);
    $is_new = $entity->isNew();

    foreach (drupal_schema_fields_sql($this->entityInfo[$table_key]) as $name) {
      $info = $schema['fields'][$name];
      $value = isset($definitions[$name]) && isset($entity->$name->value) ? $entity->$name->value : NULL;
      // If we are creating a new entity, we must not populate the record with
      // NULL values otherwise defaults would not be applied.
      if (isset($value) || !$is_new) {
        $record->$name = drupal_schema_get_field_value($info, $value);
      }
    }

    return $record;
  }

  /**
   * Maps from an entity object to the storage record of the field data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $table_key
   *   (optional) The entity key identifying the target table. Defaults to
   *   'data_table'.
   *
   * @return \stdClass
   *   The record to store.
   */
  protected function mapToDataStorageRecord(EntityInterface $entity, $table_key = 'data_table') {
    $record = $this->mapToStorageRecord($entity, $table_key);
    $record->langcode = $entity->language()->id;
    $record->default_langcode = intval($record->langcode == $entity->getUntranslated()->language()->id);
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
      $entity_class = $this->entityClass;
      $entity_class::preDelete($this, $entities);

      foreach ($entities as $entity) {
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

      if ($this->revisionDataTable) {
        $this->database->delete($this->revisionDataTable)
          ->condition($this->idKey, $ids)
          ->execute();
      }

      // Reset the cache as soon as the changes have been applied.
      $this->resetCache($ids);

      $entity_class::postDelete($this, $entities);
      foreach ($entities as $entity) {
        $this->invokeFieldMethod('delete', $entity);
        $this->deleteFieldItems($entity);
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
