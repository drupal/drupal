<?php

namespace Drupal\layout_discovery\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for layout_discovery.
 */
class LayoutDiscoveryHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name) {
    switch ($route_name) {
      case 'help.page.layout_discovery':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('Layout Discovery allows modules or themes to register layouts, and for other modules to list the available layouts and render them.') . '</p>';
        $output .= '<p>' . t('For more information, see the <a href=":layout-discovery-documentation">online documentation for the Layout Discovery module</a>.', [
          ':layout-discovery-documentation' => 'https://www.drupal.org/docs/8/api/layout-api',
        ]) . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return \Drupal::service('plugin.manager.core.layout')->getThemeImplementations();
  }

}
