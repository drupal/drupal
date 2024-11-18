<?php

declare(strict_types=1);

namespace Drupal\theme_page_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for theme_page_test.
 */
class ThemePageTestHooks {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    // Make sure that all themes are visible on the Appearance form.
    if ($type === 'theme') {
      unset($info['hidden']);
    }
  }

}
