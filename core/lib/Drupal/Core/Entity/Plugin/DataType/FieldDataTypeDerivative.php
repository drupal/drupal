<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\FieldDataTypeDerivative.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides data type plugins for each existing field type plugin.
 */
class FieldDataTypeDerivative implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    foreach (\Drupal::service('plugin.manager.entity.field.field_type')->getDefinitions() as $plugin_id => $definition) {
      $this->derivatives[$plugin_id] = $definition;
    }
    return $this->derivatives;
  }

}
