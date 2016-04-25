<?php

namespace Drupal\Core\Config\Schema;

/**
 * Defines a mapping configuration element.
 *
 * This object may contain any number and type of nested properties and each
 * property key may have its own definition in the 'mapping' property of the
 * configuration schema.
 *
 * Properties in the configuration value that are not defined in the mapping
 * will get the 'undefined' data type.
 *
 * Read https://www.drupal.org/node/1905070 for more details about configuration
 * schema, types and type resolution.
 */
class Mapping extends ArrayElement {

  /**
   * {@inheritdoc}
   */
  protected function getElementDefinition($key) {
    $value = isset($this->value[$key]) ? $this->value[$key] : NULL;
    $definition = isset($this->definition['mapping'][$key]) ? $this->definition['mapping'][$key] : array();
    return $this->buildDataDefinition($definition, $value, $key);
  }

}
