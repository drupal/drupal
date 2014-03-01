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

  /**
   * Create typed config object.
   */
  protected function parseElement($key, $data, $definition) {
    return \Drupal::service('config.typed')->create($definition, $data, $key, $this);
  }

}
