<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityListControllerInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines an interface for entity list controllers.
 */
interface EntityListControllerInterface {

  /**
   * Gets the entity storage controller.
   *
   * @return \Drupal\Core\Entity\EntityStorageControllerInterface
   *   The storage controller used by this list controller.
   */
  public function getStorageController();

  /**
   * Loads entities of this type from storage for listing.
   *
   * This allows the controller to manipulate the list, like filtering or
   * sorting the loaded entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities implementing Drupal\Core\Entity\EntityInterface.
   */
  public function load();

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   An associative array of operation link data for this list, keyed by
   *   operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - href: The path for the operation.
   *   - options: An array of URL options for the path.
   *   - weight: The weight of this operation.
   */
  public function getOperations(EntityInterface $entity);

  /**
   * Renders the list page markup to be output.
   *
   * @return string
   *   The output markup for the listing page.
   */
  public function render();

}
