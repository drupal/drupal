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
 * Read https://www.drupal.org/node/1905070 for more details about configuration
 * schema, types and type resolution.
 */
class Sequence extends ArrayElement {

  /**
   * {@inheritdoc}
   */
  protected function getElementDefinition($key) {
    $value = isset($this->value[$key]) ? $this->value[$key] : NULL;
    // @todo: Remove BC layer for sequence with hyphen in front. https://www.drupal.org/node/2444979
    $definition = array();
    if (isset($this->definition['sequence'][0])) {
      $definition = $this->definition['sequence'][0];
    }
    elseif ($this->definition['sequence']) {
      $definition = $this->definition['sequence'];
    }
    return $this->buildDataDefinition($definition, $value, $key);
  }

}
