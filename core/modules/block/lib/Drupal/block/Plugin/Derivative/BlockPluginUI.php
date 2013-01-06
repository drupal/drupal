<?php

/**
 * Contains \Drupal\block\Plugin\Derivative\BlockPluginUI.
 */

namespace Drupal\block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides block plugin UI plugin definitions for all themes.
 *
 * @todo Add documentation to this class.
 *
 * @see \Drupal\block\Plugin\system\plugin_ui\BlockPluginUI
 */
class BlockPluginUI implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   *
   * @todo Add documentation to this method.
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Provide a derivative of the plugin UI for each theme.
    foreach (list_themes() as $key => $theme) {
      $this->derivatives[$key] = $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
