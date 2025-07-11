<?php

namespace Drupal\toolbar\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for toolbar.
 */
class ToolbarThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for HTML document templates.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    if (!\Drupal::currentUser()->hasPermission('access toolbar')) {
      return;
    }
    $variables['attributes']['class'][] = 'toolbar-loading';
  }

}
