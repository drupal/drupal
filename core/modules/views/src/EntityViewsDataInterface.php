<?php

namespace Drupal\views;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface to integrate an entity type with views.
 */
interface EntityViewsDataInterface {

  /**
   * Returns views data for the entity type.
   *
   * @return array
   *   Views data in the format of hook_views_data().
   */
  public function getViewsData();

  /**
   * Gets the table of an entity type to be used as base table in views.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return string
   *   The name of the base table in views.
   */
  public function getViewsTableForEntityType(EntityTypeInterface $entity_type);

}
