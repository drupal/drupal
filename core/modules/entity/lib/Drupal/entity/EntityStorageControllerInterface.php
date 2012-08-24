<?php

/**
 * @file
 * Definition of Drupal\entity\EntityStorageControllerInterface.
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
 * Drupal\entity\DatabaseStorageController instead of implementing this
 * interface directly.
 */
interface EntityStorageControllerInterface extends StorageControllerInterface {

}
