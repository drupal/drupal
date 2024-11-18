<?php

declare(strict_types=1);

namespace Drupal\js_deprecation_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for js_deprecation_test.
 */
class JsDeprecationTestHooks {

  /**
   * Implements hook_js_settings_alter().
   */
  #[Hook('js_settings_alter')]
  public function jsSettingsAlter(&$settings): void {
    $settings['suppressDeprecationErrors'] = FALSE;
  }

}
