<?php

/**
 * @file
 * Contains Drupal\Component\Plugin\Derivative\DerivativeBase.
 */

namespace Drupal\Component\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Default version of getDerivativeDefinition() common to most concrete
 * implementations of DerivativeInterface.
 *
 * See the Aggregator and Mock block derivers for different implementations.
 */
abstract class DerivativeBase implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    return $this->derivatives;
  }
}
