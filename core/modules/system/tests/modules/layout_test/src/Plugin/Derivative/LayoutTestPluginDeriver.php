<?php

namespace Drupal\layout_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * @todo.
 */
class LayoutTestPluginDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!$this->derivatives) {
      $this->derivatives['one'] = $base_plugin_definition;
      $this->derivatives['two'] = $base_plugin_definition;
      $this->derivatives['three'] = $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
