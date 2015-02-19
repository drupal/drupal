<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Element.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\TypedData\TypedData;

/**
 * Defines a generic configuration element.
 */
abstract class Element extends TypedData {

  /**
   * The configuration value.
   *
   * @var mixed
   */
  protected $value;

}
