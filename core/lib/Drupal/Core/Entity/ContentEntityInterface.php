<?php

namespace Drupal\Core\Entity;

/**
 * Defines a common interface for all content entity objects.
 *
 * Content entities use fields for all their entity properties and are
 * translatable and revisionable, while translations and revisions can be
 * enabled per entity type. It's best practice to always implement
 * ContentEntityInterface for content-like entities that should be stored in
 * some database, and enable/disable revisions and translations as desired.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\Entity\ContentEntityBase
 *
 * @ingroup entity_api
 */
interface ContentEntityInterface extends \Traversable, FieldableEntityInterface, TranslatableRevisionableInterface {

  /**
   * Gets the loaded Revision ID of the entity.
   *
   * @return int
   *   The loaded Revision identifier of the entity, or NULL if the entity
   *   does not have a revision identifier.
   */
  public function getLoadedRevisionId();

  /**
   * Updates the loaded Revision ID with the revision ID.
   *
   * This method should not be used, it could unintentionally cause the original
   * revision ID property value to be lost.
   *
   * @internal
   *
   * @return $this
   */
  public function updateLoadedRevisionId();

}
