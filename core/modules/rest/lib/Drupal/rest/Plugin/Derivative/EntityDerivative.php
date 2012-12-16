<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\Derivative\EntityDerivative.
 */

namespace Drupal\rest\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides a resource plugin definition for every entity type.
 */
class EntityDerivative implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * Implements DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * Implements DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      // Add in the default plugin configuration and the resource type.
      foreach (entity_get_info() as $entity_type => $entity_info) {
        $this->derivatives[$entity_type] = array(
          'id' => 'entity:' . $entity_type,
          'entity_type' => $entity_type,
          'serialization_class' => $entity_info['class'],
          'label' => $entity_info['label'],
        );
        $this->derivatives[$entity_type] += $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }
}
