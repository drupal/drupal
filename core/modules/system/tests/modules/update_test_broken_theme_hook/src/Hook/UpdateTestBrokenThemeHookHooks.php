<?php

declare(strict_types=1);

namespace Drupal\update_test_broken_theme_hook\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for update_test_broken_theme_hook.
 */
class UpdateTestBrokenThemeHookHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    throw new \Exception('This mimics an exception caused by unstable dependencies.');
  }

}
