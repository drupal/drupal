<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Derivative\ThemeLocalTask.
 */

namespace Drupal\system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

/**
 * Provides dynamic tabs based on active themes.
 */
class ThemeLocalTask extends DerivativeBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (list_themes() as $theme_name => $theme) {
      if ($theme->status) {
        $this->derivatives[$theme_name] = $base_plugin_definition;
        $this->derivatives[$theme_name]['title'] = $theme->info['name'];
        $this->derivatives[$theme_name]['route_parameters'] = array('theme' => $theme_name);
      }
    }
    return $this->derivatives;
  }

}
