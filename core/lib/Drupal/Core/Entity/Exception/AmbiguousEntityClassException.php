<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Exception\AmbiguousEntityClassException.
 */

namespace Drupal\Core\Entity\Exception;

/**
 * Exception thrown if multiple subclasses exist for an entity.
 *
 * This might occur if an entity is subclassed multiple times and the base
 * class is altered to use one of the subclasses instead. If a static method on
 * the base class is then invoked it is impossible to determine which of the
 * subclasses is responsible for it.
 *
 * @see hook_entity_info_alter()
 * @see \Drupal\Core\Entity\Entity::getEntityTypeFromStaticClass()
 */
class AmbiguousEntityClassException extends \Exception {

  /**
   * Constructs an AmbiguousEntityClassException.
   *
   * @param string $class
   *   The entity parent class.
   */
  public function __construct($class) {
    $message = sprintf('Multiple subclasses provide an entity type for %s.', $class);
    parent::__construct($message);
  }

}
