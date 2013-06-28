<?php

/**
 * Contains \Drupal\block\Plugin\Derivative\BlockPluginUI.
 */

namespace Drupal\block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

/**
 * Provides block plugin UI plugin definitions for all themes.
 *
 * @todo Add documentation to this class.
 *
 * @see \Drupal\block\Plugin\system\plugin_ui\BlockPluginUI
 */
class BlockPluginUI extends DerivativeBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Provide a derivative of the plugin UI for each theme.
    foreach (list_themes() as $key => $theme) {
      $this->derivatives[$key] = $base_plugin_definition;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
