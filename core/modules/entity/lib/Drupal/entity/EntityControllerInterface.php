<?php

/**
 * @file
 * Definition of Drupal\entity\EntityControllerInterface.
 */

namespace Drupal\entity;

/**
 * Defines a common interface for entity controller classes.
 *
 * All entity controller classes specified via the 'controller class' key
 * returned by hook_entity_info() or hook_entity_info_alter() have to implement
 * this interface.
 *
 * Most simple, SQL-based entity controllers will do better by extending
 * Drupal\entity\EntityController instead of implementing this interface
 * directly.
 */
interface EntityControllerInterface {

  /**
   * Constructs a new Drupal\entity\EntityControllerInterface object.
   *
   * @param $entityType
   *   The entity type for which the instance is created.
   */
  public function __construct($entityType);

  /**
   * Resets the internal, static entity cache.
   *
   * @param $ids
   *   (optional) If specified, the cache is reset for the entities with the
   *   given ids only.
   */
  public function resetCache(array $ids = NULL);

  /**
   * Loads one or more entities.
   *
   * @param $ids
   *   An array of entity IDs, or FALSE to load all entities.
   * @param $conditions
   *   An array of conditions in the form 'field' => $value.
   *
   * @return
   *   An array of entity objects indexed by their ids.
   */
  public function load($ids = array(), $conditions = array());
}
