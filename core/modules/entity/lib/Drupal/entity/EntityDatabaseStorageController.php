<?php

/**
 * @file
 * Definition of Drupal\entity\EntityDatabaseStorageController.
 */

namespace Drupal\entity;

/**
 * Implements the entity storage controller interface for the database.
 */
class EntityDatabaseStorageController extends EntityController implements EntityStorageControllerInterface {

  /**
   * Implements Drupal\entity\EntityStorageControllerInterface::create().
   */
  public function create(array $values) {
    $class = isset($this->entityInfo['entity class']) ? $this->entityInfo['entity class'] : 'Drupal\entity\Entity';
    return new $class($values, $this->entityType);
  }

  /**
   * Implements Drupal\entity\EntityStorageControllerInterface::delete().
   */
  public function delete($ids) {
    $entities = $ids ? $this->load($ids) : FALSE;
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }
    $transaction = db_transaction();

    try {
      $this->preDelete($entities);
      foreach ($entities as $id => $entity) {
        $this->invokeHook('predelete', $entity);
      }
      $ids = array_keys($entities);

      db_delete($this->entityInfo['base table'])
        ->condition($this->idKey, $ids, 'IN')
        ->execute();
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

  /**
   * Implements Drupal\entity\EntityStorageControllerInterface::save().
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

      if (!$entity->isNew()) {
        $return = drupal_write_record($this->entityInfo['base table'], $entity, $this->idKey);
        $this->resetCache(array($entity->{$this->idKey}));
        $this->postSave($entity, TRUE);
        $this->invokeHook('update', $entity);
      }
      else {
        $return = drupal_write_record($this->entityInfo['base table'], $entity);
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
    catch (Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   */
  protected function preSave(EntityInterface $entity) { }

  /**
   * Acts on a saved entity before the insert or update hook is invoked.
   *
   * Used after the entity is saved, but before invoking the insert or update
   * hook.
   *
   * @param $update
   *   (bool) TRUE if the entity has been updated, or FALSE if it has been
   *   inserted.
   */
  protected function postSave(EntityInterface $entity, $update) { }

  /**
   * Acts on entities before they are deleted.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   */
  protected function preDelete($entities) { }

  /**
   * Acts on deleted entities before the delete hook is invoked.
   *
   * Used after the entities are deleted but before invoking the delete hook.
   */
  protected function postDelete($entities) { }

  /**
   * Invokes a hook on behalf of the entity.
   *
   * @param $hook
   *   One of 'presave', 'insert', 'update', 'predelete', or 'delete'.
   * @param $entity
   *   The entity object.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    if (!empty($this->entityInfo['fieldable']) && function_exists($function = 'field_attach_' . $hook)) {
      $function($this->entityType, $entity);
    }
    // Invoke the hook.
    module_invoke_all($this->entityType . '_' . $hook, $entity);
    // Invoke the respective entity-level hook.
    module_invoke_all('entity_' . $hook, $entity, $this->entityType);
  }
}
