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
      $values = array();
      foreach ($record as $name => $value) {
        // Skip the item delta and item value levels but let the field assign
        // the value as suiting. This avoids unnecessary array hierarchies and
        // saves memory here.
        $values[$name][Language::LANGCODE_DEFAULT] = $value;
      }
      $bundle = $this->bundleKey ? $record->{$this->bundleKey} : FALSE;
      // Turn the record into an entity class.
      $entities[$id] = new $this->entityClass($values, $this->entityType, $bundle);
    }
    $this->attachPropertyData($entities, $load_revision);
    return $entities;
  }

  /**
   * Attaches property data in all languages for translatable properties.
   *
   * @param array &$entities
   *   Associative array of entities, keyed on the entity ID.
   * @param boolean $load_revision
   *   (optional) TRUE if the revision should be loaded, defaults to FALSE.
   */
  protected function attachPropertyData(array &$entities, $load_revision = FALSE) {
    if ($this->dataTable) {
      $query = $this->database->select($this->dataTable, 'data', array('fetch' => PDO::FETCH_ASSOC))
        ->fields('data')
        ->condition($this->idKey, array_keys($entities))
        ->orderBy('data.' . $this->idKey);
      if ($load_revision) {
        // Get revision ID's.
        $revision_ids = array();
        foreach ($entities as $id => $entity) {
          $revision_ids[] = $entity->get($this->revisionKey)->value;
        }
        $query->condition($this->revisionKey, $revision_ids);
      }
      $data = $query->execute();

      // Fetch the field definitions to check which field is translatable.
      $field_definition = $this->getFieldDefinitions(array());
      $data_fields = array_flip(drupal_schema_fields_sql($this->entityInfo['data_table']));

      foreach ($data as $values) {
        $id = $values[$this->idKey];
        // Field values in default language are stored with
        // Language::LANGCODE_DEFAULT as key.
        $langcode = empty($values['default_langcode']) ? $values['langcode'] : Language::LANGCODE_DEFAULT;
        $translation = $entities[$id]->getTranslation($langcode);

        foreach ($field_definition as $name => $definition) {
          // Set translatable properties only.
          if (isset($data_fields[$name]) && !empty($definition['translatable'])) {
            // @todo Figure out how to determine which property has to be set.
            // Currently it's guessing, and guessing is evil!
            $property_definition = $translation->{$name}->getPropertyDefinitions();
            $translation->{$name}->{key($property_definition)} = $values[$name];
          }
          // Avoid initializing configurable fields before loading them.
          elseif (!empty($definition['configurable'])) {
            unset($entities[$id]->fields[$name]);
          }
        }
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
    $record = $this->mapToRevisionStorageRecord($entity);

    // When saving a new revision, set any existing revision ID to NULL so as to
    // ensure that a new revision will actually be created.
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
      drupal_write_record($this->revisionTable, $record, $this->revisionKey);
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
  protected function mapToRevisionStorageRecord(EntityInterface $entity) {
    $record = new \stdClass();
    foreach (drupal_schema_fields_sql($this->entityInfo['revision_table']) as $name) {
      if (isset($entity->$name->value)) {
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

    $record = new \stdClass();
    foreach (drupal_schema_fields_sql($this->entityInfo['data_table']) as $name) {
      $record->$name = $translation->$name->value;
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
    catch (Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage, $e->getCode, $e);
    }
  }
}
