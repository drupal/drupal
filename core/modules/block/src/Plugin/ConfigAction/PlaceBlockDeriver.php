<?php

declare(strict_types=1);

namespace Drupal\block\Plugin\ConfigAction;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines a deriver for the `placeBlock` config action.
 *
 * This creates two actions: `placeBlockInDefaultTheme`, and
 * `placeBlockInAdminTheme`. They behave identically except for which theme
 * they target.
 */
final class PlaceBlockDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['placeBlockInAdminTheme'] = [
      'which_theme' => 'admin',
    ] + $base_plugin_definition;
    $this->derivatives['placeBlockInDefaultTheme'] = [
      'which_theme' => 'default',
    ] + $base_plugin_definition;

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
