<?php

declare(strict_types=1);

namespace Drupal\js_testing_log_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for js_testing_log_test.
 */
class JsTestingLogTestHooks {

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Unconditionally attach an asset to the page.
    $attachments['#attached']['library'][] = 'js_testing_log_test/deprecation_log';
  }

  /**
   * Implements hook_js_settings_alter().
   */
  #[Hook('js_settings_alter')]
  public function jsSettingsAlter(&$settings): void {
    $settings['suppressDeprecationErrors'] = FALSE;
  }

}
