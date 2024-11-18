<?php

declare(strict_types=1);

namespace Drupal\locale_test_translate\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for locale_test_translate.
 */
class LocaleTestTranslateHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * By default this modules is hidden but once enabled it behaves like a normal
   * (not hidden) module. This hook implementation changes the .info.yml data by
   * setting the hidden status to FALSE.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    if ($file->getName() == 'locale_test_translate') {
      // Don't hide the module.
      $info['hidden'] = FALSE;
    }
  }

  /**
   * Implements hook_modules_installed().
   *
   * @see \Drupal\Tests\locale\Functional\LocaleConfigTranslationImportTest::testConfigTranslationWithForeignLanguageDefault
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules, $is_syncing) {
    // Ensure that writing to configuration during install does not cause
    // \Drupal\locale\LocaleConfigSubscriber to create incorrect translations due
    // the configuration langcode and data being out-of-sync.
    \Drupal::configFactory()->getEditable('locale_test_translate.settings')->set('key_set_during_install', TRUE)->save();
  }

}
