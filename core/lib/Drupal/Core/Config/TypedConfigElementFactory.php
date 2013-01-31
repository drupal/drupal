<?php

/**
 * @file
 * Contains \Drupal\Core\Config\TypedConfigElementFactory.
 */

namespace Drupal\Core\Config;

use Drupal\Core\TypedData\TypedDataFactory;

/**
 * A factory for typed config element objects.
 *
 * This factory merges the type definition into the element definition prior to
 * creating the instance.
 */
class TypedConfigElementFactory extends TypedDataFactory {

  /**
   * Overrides Drupal\Core\TypedData\TypedDataFactory::createInstance().
   */
  public function createInstance($plugin_id, array $configuration, $name = NULL, $parent = NULL) {
    $type_definition = $this->discovery->getDefinition($plugin_id);
    $configuration += $type_definition;
    return parent::createInstance($plugin_id, $configuration, $name, $parent);
  }
}
