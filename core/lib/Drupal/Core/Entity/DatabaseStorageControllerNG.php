<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\DatabaseStorageControllerNG.
 */

namespace Drupal\Core\Entity;

use PDO;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Uuid\Uuid;

/**
 * Implements Field API specific enhancements to the DatabaseStorageController class.
 *
 * @todo: Once all entity types have been converted, merge improvements into the
 * DatabaseStorageController class.
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
   * Overrides DatabaseStorageController::__construct().
   */
  public function __construct($entityType) {
    parent::__construct($entityType);
    $this->bundleKey = !empty($this->entityInfo['entity_keys']['bundle']) ? $this->entityInfo['entity_keys']['bundle'] : FALSE;
    $this->entityClass = $this->entityInfo['class'];

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
   *   property one can pass:
   *   @code
   *     $values = array('name' => array(0 => array('value' => 'the name')));
   *   @endcode
   *   or
   *   @code
   *     $values = array('name' => array('value' => 'the name'));
   *   @endcode
   *   If the 'name' field is a defined as 'string_item' which supports
   *   setting by string value, it's also possible to just pass the name string:
   *   @code
   *     $values = array('name' => 'the name');
   *   @endcode
   *
   * @return Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function create(array $values) {
    // We have to determine the bundle first.
    $bundle = $this->bundleKey ? $values[$this->bundleKey] : FALSE;
    $entity = new $this->entityClass(array(), $this->entityType, $bundle);

    // Set all other given values.
    foreach ($values as $name => $value) {
      $entity->$name = $value;
    }

    // Assign a new UUID if there is none yet.
    if ($this->uuidKey && !isset($entity->{$this->uuidKey}->value)) {
      $uuid = new Uuid();
      $entity->{$this->uuidKey}->value = $uuid->generate();
    }
    return $entity;
  }

  /**
   * Overrides DatabaseStorageController::attachLoad().
   *
   * Added mapping from storage records to entities.
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    // Now map the record values to the according entity properties and
    // activate compatibility mode.
    $queried_entities = $this->mapFromStorageRecords($queried_entities, $load_revision);

    // Attach fields.
    if ($this->entityInfo['fieldable']) {
      // Prepare BC compatible entities for field API.
      $bc_entities = array();
      foreach ($queried_entities as $key => $entity) {
        $bc_entities[$key] = $entity->getBCEntity();
      }

      if ($load_revision) {
        field_attach_load_revision($this->entityType, $bc_entities);
      }
      else {
        field_attach_load($this->entityType, $bc_entities);
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

    foreach ($records as $id => $record) {
      $values = array();
      foreach ($record as $name => $value) {
        // Avoid unnecessary array hierarchies to save memory.
        $values[$name][LANGUAGE_DEFAULT] = $value;
      }
      $bundle = $this->bundleKey ? $record->{$this->bundleKey} : FALSE;
      // Turn the record into an entity class.
      $records[$id] = new $this->entityClass($values, $this->entityType, $bundle);
    }
    return $records;
  }

  /**
   * Overrides DatabaseStorageController::save().
   *
   * Added mapping from entities to storage records before saving.
   */
  public function save(EntityInterface $entity) {
    $transaction = db_transaction();
    try {
      // Load the stored entity, if any.
      if (!$entity->isNew() && !isset($entity->original)) {
        $entity->original = entity_load_unchanged($this->entityType, $entity->id());
      }

      $this->preSave($entity);
      $this->invokeHook('presave', $entity);

      // Create the storage record to be saved.
      $record = $this->maptoStorageRecord($entity);

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
        $this->resetCache(array($entity->id()));
        $this->postSave($entity, TRUE);
        $this->invokeHook('update', $entity);
      }
      else {
        $return = drupal_write_record($this->entityInfo['base_table'], $record);
        if ($this->revisionKey) {
          $entity->{$this->idKey}->value = $record->{$this->idKey};
          $record->{$this->revisionKey} = $this->saveRevision($entity);
        }
        // Reset general caches, but keep caches specific to certain entities.
        $this->resetCache(array());

        $entity->{$this->idKey}->value = $record->{$this->idKey};
        $entity->enforceIsNew(FALSE);
        $this->postSave($entity, FALSE);
        $this->invokeHook('insert', $entity);
      }
      $entity->updateOriginalValues();

      // Ignore slave server temporarily.
      db_ignore_slave();
      unset($entity->original);

      return $return;
    }
    catch (Exception $e) {
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
        db_update($this->entityInfo['base_table'])
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
      $function($entity->getBCEntity());
    }

    // Invoke the hook.
    module_invoke_all($this->entityType . '_' . $hook, $entity);
    // Invoke the respective entity-level hook.
    module_invoke_all('entity_' . $hook, $entity, $this->entityType);
  }

  /**
   * Maps from an entity object to the storage record of the base table.
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    $record = new \stdClass();
    foreach ($this->entityInfo['schema_fields_sql']['base_table'] as $name) {
      $record->$name = $entity->$name->value;
    }
    return $record;
  }

  /**
   * Maps from an entity object to the storage record of the revision table.
   */
  protected function mapToRevisionStorageRecord(EntityInterface $entity) {
    $record = new \stdClass();
    foreach ($this->entityInfo['schema_fields_sql']['revision_table'] as $name) {
      $record->$name = $entity->$name->value;
    }
    return $record;
  }
}
