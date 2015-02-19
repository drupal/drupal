<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Undefined.
 */

namespace Drupal\Core\Config\Schema;

/**
 * Undefined configuration element.
 */
class Undefined extends Element {

  /**
   * {@inheritdoc}.
   */
  public function validate() {
    return isset($this->value);
  }
}
