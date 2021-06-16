<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for access to an entity's published state.
 */
interface EntityPublishedInterface extends EntityInterface {

  /**
   * Returns whether or not the entity is published.
   *
   * @return bool
   *   TRUE if the entity is published, FALSE otherwise.
   */
  public function isPublished();

  /**
   * Sets the entity as published.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\EntityPublishedInterface::setUnpublished()
   */
  public function setPublished();

  /**
   * Sets the entity as unpublished.
   *
   * @return $this
   */
  public function setUnpublished();

}
