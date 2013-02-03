<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Sequence.
 */

namespace Drupal\Core\Config\Schema;

/**
 * Generic configuration property.
 */
class Property extends Element {

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    return isset($this->value);
  }

}
