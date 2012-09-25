<?php

/**
 * @file
 * Definition of Drupal\views_ui_listing\EntityListControllerInterface.
 */

namespace Drupal\views_ui_listing;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for entity list controllers.
 */
interface EntityListControllerInterface {

  /**
   * Gets the entity storage controller.
   *
   * @return Drupal\Core\Entity\EntityStorageControllerInterface
   *   The storage controller used by this list controller.
   */
  public function getStorageController();

  /**
   * Loads entities of this type from storage for listing.
   *
   * @return array
   *   An array of entities implementing Drupal\Core\Entity\EntityInterface.
   */
  public function load();

  /**
   * Provides an array of information to render the operation links.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   A array of operation link data to use in
   *   EntityListControllerInterface::buildOperations().
   */
  public function getOperations(EntityInterface $entity);

  /**
   * Builds the header row.
   *
   * @return array
   *   An array of header strings.
   */
  public function buildHeader();

  /**
   * Builds an array of data for each row.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity for this row of the list.
   *
   * @return array
   *   An array of fields to use for this entity.
   */
  public function buildRow(EntityInterface $entity);

  /**
   * Renders a list of operation links.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   */
  public function buildOperations(EntityInterface $entity);

  /**
   * Renders the list page markup to be output.
   *
   * @return string
   *   The output markup for the listing page.
   */
  public function render();

}
