<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityTypeListenerInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines an interface for reacting to entity type creation, deletion, and updates.
 *
 * @todo Convert to Symfony events: https://www.drupal.org/node/2332935
 */
interface EntityTypeListenerInterface {

  /**
   * Reacts to the creation of the entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type being created.
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type);

  /**
   * Reacts to the update of the entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The updated entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The original entity type definition.
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original);

  /**
   * Reacts to the deletion of the entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type being deleted.
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type);

}
