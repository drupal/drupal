<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Derivative\SelectionBase.
 */

namespace Drupal\entity_reference\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Base class for selection plugins provided by Entity Reference.
 */
class SelectionBase implements DerivativeInterface {

  /**
   * Holds the list of plugin derivatives.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $supported_entities = array(
      'comment',
      'file',
      'node',
      'taxonomy_term',
      'user'
    );
    foreach (entity_get_info() as $entity_type => $info) {
      if (!in_array($entity_type, $supported_entities)) {
        $this->derivatives[$entity_type] = $base_plugin_definition;
        $this->derivatives[$entity_type]['label'] = t('@enitty_type selection', array('@entity_type' => $info['label']));
      }
    }
    return $this->derivatives;
  }
}
