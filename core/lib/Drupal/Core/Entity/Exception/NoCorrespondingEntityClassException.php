<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException.
 */

namespace Drupal\Core\Entity\Exception;

/**
 * Exception thrown if an entity type is not represented by a class.
 *
 * This might occur by calling a static method on an abstract class.
 *
 * @see \Drupal\Core\Entity\Entity::getEntityTypeFromStaticClass()
 */
class NoCorrespondingEntityClassException extends \Exception {

  /**
   * Constructs an NoCorrespondingEntityClassException.
   *
   * @param string $class
   *   The class which does not correspond to an entity type.
   */
  public function __construct($class) {
    $message = sprintf('The %s class does not correspond to an entity type.', $class);
    parent::__construct($message);
  }

}
