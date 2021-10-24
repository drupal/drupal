<?php

namespace Drupal\Core\Entity\Exception;

/**
 * Exception thrown if a bundle class is defined for multiple bundles.
 *
 * @see \Drupal\Core\Entity\ContentEntityStorageBase::getBundleFromClass()
 * @see \Drupal\Core\Entity\EntityTypeRepository::getEntityTypeFromClass()
 */
class AmbiguousBundleClassException extends AmbiguousEntityClassException {

  /**
   * Constructs an AmbiguousBundleClassException.
   *
   * @param string $class
   *   The bundle class which is defined for multiple bundles.
   */
  public function __construct(string $class) {
    $message = sprintf('Multiple bundles are using the bundle class %s.', $class);
    parent::__construct($message);
  }

}
