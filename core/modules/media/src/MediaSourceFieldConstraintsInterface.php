<?php

namespace Drupal\media;

/**
 * Defines an interface for a media source with source field constraints.
 *
 * This allows a media source to optionally add source field validation
 * constraints for media items. To add constraints at the entity level, a
 * media source can also implement MediaSourceEntityConstraintsInterface.
 *
 * @see \Drupal\media\MediaSourceInterface
 * @see \Drupal\media\MediaSourceEntityConstraintsInterface
 * @see \Drupal\media\MediaSourceBase
 * @see \Drupal\media\Entity\Media
 */
interface MediaSourceFieldConstraintsInterface extends MediaSourceInterface {

  /**
   * Gets media source-specific validation constraints for a source field.
   *
   * @return \Symfony\Component\Validator\Constraint[]
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   */
  public function getSourceFieldConstraints();

}
