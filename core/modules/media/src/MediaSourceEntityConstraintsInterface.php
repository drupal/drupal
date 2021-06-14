<?php

namespace Drupal\media;

/**
 * Defines an interface for a media source with entity constraints.
 *
 * This allows a media source to optionally add entity validation constraints
 * for media items. To add constraints at the source field level, a media source
 * can also implement MediaSourceFieldConstraintsInterface.
 *
 * @see \Drupal\media\MediaSourceInterface
 * @see \Drupal\media\MediaSourceFieldConstraintsInterface.php
 * @see \Drupal\media\MediaSourceBase
 * @see \Drupal\media\Entity\Media
 */
interface MediaSourceEntityConstraintsInterface extends MediaSourceInterface {

  /**
   * Gets media source-specific validation constraints for a media item.
   *
   * @return \Symfony\Component\Validator\Constraint[]
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   */
  public function getEntityConstraints();

}
