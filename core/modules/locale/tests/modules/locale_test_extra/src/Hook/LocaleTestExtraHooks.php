<?php

declare(strict_types=1);

namespace Drupal\locale_test_extra\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for locale_test_extra.
 */
class LocaleTestExtraHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * By default this modules is hidden but once enabled it behaves like a normal
   * (not hidden) module. This hook implementation changes the .info.yml data by
   * setting the hidden status to FALSE.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    if ($file->getName() == 'locale_test_extra') {
      // Don't hide the module.
      $info['hidden'] = FALSE;
    }
  }

}
