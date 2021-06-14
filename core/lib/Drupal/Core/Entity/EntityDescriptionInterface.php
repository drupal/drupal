<?php

namespace Drupal\Core\Entity;

/**
 * Defines the interface for entities that have a description.
 */
interface EntityDescriptionInterface extends EntityInterface {

  /**
   * Gets the entity description.
   *
   * @return string
   *   The entity description.
   */
  public function getDescription();

  /**
   * Sets the entity description.
   *
   * @param string $description
   *   The entity description.
   *
   * @return $this
   */
  public function setDescription($description);

}
