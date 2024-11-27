<?php

declare(strict_types=1);

namespace Drupal\settings_tray_test_css\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for settings_tray_test_css.
 */
class SettingsTrayTestCssHooks {

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Unconditionally attach an asset to the page.
    $attachments['#attached']['library'][] = 'settings_tray_test_css/drupal.css_fix';
  }

}
