<?php

/**
 * Contains \Drupal\menu_test\Plugin\Derivative\LocalTaskTest
 */

namespace Drupal\menu_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

class LocalTaskTest extends DerivativeBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $weight = $base_plugin_definition['weight'];
    foreach (array('derive1' => 'Derive 1', 'derive2' => 'Derive 2') as $key => $title) {
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['title'] = $title;
      $this->derivatives[$key]['route_parameters'] = array('placeholder' => $key);
      $this->derivatives[$key]['weight'] = $weight++; // ensure weights for testing.
    }
    return $this->derivatives;
  }
}
