<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines an interface for managing entity definition updates.
 *
 * During the application lifetime, the definitions of various entity types and
 * their data components (e.g., fields for fieldable entity types) can change.
 * For example, updated code can be deployed. Some entity handlers may need to
 * perform complex or long-running logic in response to the change. For
 * example, a SQL-based storage handler may need to update the database schema.
 *
 * To support this, \Drupal\Core\Entity\EntityManagerInterface has methods to
 * retrieve the last installed definitions as well as the definitions specified
 * by the current codebase. It also has create/update/delete methods to bring
 * the former up to date with the latter.
 *
 * However, it is not the responsibility of the entity manager to decide how to
 * report the differences or when to apply each update. This interface is for
 * managing that.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getDefinition()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getLastInstalledDefinition()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldStorageDefinitions()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getLastInstalledFieldStorageDefinitions()
 * @see \Drupal\Core\Entity\EntityTypeListenerInterface
 * @see \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
 */
interface EntityDefinitionUpdateManagerInterface {

  /**
   * Checks if there are any definition updates that need to be applied.
   *
   * @return bool
   *   TRUE if updates are needed.
   */
  public function needsUpdates();

  /**
   * Returns a human readable summary of the detected changes.
   *
   * @return array
   *   An associative array keyed by entity type id. Each entry is an array of
   *   human-readable strings, each describing a change.
   */
  public function getChangeSummary();

  /**
   * Applies all the detected valid changes.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   This exception is thrown if a change cannot be applied without
   *   unacceptable data loss. In such a case, the site administrator needs to
   *   apply some other process, such as a custom update function or a
   *   migration via the Migrate module.
   */
  public function applyUpdates();

}
