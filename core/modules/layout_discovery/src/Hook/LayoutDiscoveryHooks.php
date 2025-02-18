<?php

namespace Drupal\layout_discovery\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for layout_discovery.
 */
class LayoutDiscoveryHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name): ?string {
    switch ($route_name) {
      case 'help.page.layout_discovery':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('Layout Discovery allows modules or themes to register layouts, and for other modules to list the available layouts and render them.') . '</p>';
        $output .= '<p>' . $this->t('For more information, see the <a href=":layout-discovery-documentation">online documentation for the Layout Discovery module</a>.', [
          ':layout-discovery-documentation' => 'https://www.drupal.org/docs/8/api/layout-api',
        ]) . '</p>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return \Drupal::service('plugin.manager.core.layout')->getThemeImplementations();
  }

}
