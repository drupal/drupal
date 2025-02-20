<?php

declare(strict_types=1);

namespace Drupal\menu_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Tests derivative for testing local tasks.
 */
class LocalTaskTest extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $weight = $base_plugin_definition['weight'];
    foreach (['derive1' => 'Derive 1', 'derive2' => 'Derive 2'] as $key => $title) {
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['title'] = $title;
      $this->derivatives[$key]['route_parameters'] = ['placeholder' => $key];
      // Ensure weights for testing.
      $this->derivatives[$key]['weight'] = $weight++;
    }
    return $this->derivatives;
  }

}
