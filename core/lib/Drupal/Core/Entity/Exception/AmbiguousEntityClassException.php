<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Exception\AmbiguousEntityClassException.
 */

namespace Drupal\Core\Entity\Exception;

/**
 * Exception thrown if multiple entity types exist for an entity class.
 *
 * @see hook_entity_info_alter()
 */
class AmbiguousEntityClassException extends \Exception {

  /**
   * Constructs an AmbiguousEntityClassException.
   *
   * @param string $class
   *   The entity parent class.
   */
  public function __construct($class) {
    $message = sprintf('Multiple entity types found for %s.', $class);
    parent::__construct($message);
  }

}
