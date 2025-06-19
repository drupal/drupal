<?php

declare(strict_types=1);

namespace Drupal\js_displace\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for js_displace module.
 */
class JsDisplaceThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    $variables['#attached']['library'][] = 'core/drupal.displace';
  }

}
