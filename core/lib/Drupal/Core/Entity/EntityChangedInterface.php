<?php

namespace Drupal\Core\Entity;

/**
 * Defines an interface for entity change timestamp tracking.
 *
 * This data may be useful for more precise cache invalidation (especially
 * on the client side) and concurrent editing locking.
 *
 * The entity system automatically adds in the 'EntityChanged' constraint for
 * entity types implementing this interface in order to disallow concurrent
 * editing.
 *
 * @see \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraint
 */
interface EntityChangedInterface extends EntityInterface {

  /**
   * Gets the timestamp of the last entity change for the current translation.
   *
   * @return int
   *   The timestamp of the last entity save operation.
   */
  public function getChangedTime();

  /**
   * Sets the timestamp of the last entity change for the current translation.
   *
   * @param int $timestamp
   *   The timestamp of the last entity save operation.
   *
   * @return $this
   */
  public function setChangedTime($timestamp);

  /**
   * Gets the timestamp of the last entity change across all translations.
   *
   * This method will return the highest timestamp across all translations. To
   * check that no translation is older than in another version of the entity
   * (e.g. to avoid overwriting newer translations with old data), compare each
   * translation to the other version individually.
   *
   * @return int
   *   The timestamp of the last entity save operation across all
   *   translations.
   */
  public function getChangedTimeAcrossTranslations();

}
