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
      foreach (['one', 'two', 'three'] as $number) {
        $this->derivatives[$number] = clone $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }

}
