<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Sequence.
 */

namespace Drupal\Core\Config\Schema;

/**
 * Defines a configuration element of type Sequence.
 *
 * This object may contain any number and type of nested elements that share
 * a common definition in the 'sequence' property of the configuration schema.
 *
 * Read https://drupal.org/node/1905070 for more details about configuration
 * schema, types and type resolution.
 */
class Sequence extends ArrayElement {

  /**
   * {@inheritdoc}
   */
  protected function getElementDefinition($key) {
    $value = isset($this->value[$key]) ? $this->value[$key] : NULL;
    $definition = isset($this->definition['sequence'][0]) ? $this->definition['sequence'][0] : array();
    return $this->buildDataDefinition($definition, $value, $key);
  }

}
