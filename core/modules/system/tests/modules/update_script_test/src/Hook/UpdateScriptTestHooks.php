<?php

declare(strict_types=1);

namespace Drupal\update_script_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for update_script_test.
 */
class UpdateScriptTestHooks {

  /**
   * Implements hook_cache_flush().
   *
   * This sets a message to confirm that all caches are cleared whenever
   * update.php completes.
   *
   * @see UpdateScriptFunctionalTest::testRequirements()
   */
  #[Hook('cache_flush')]
  public function cacheFlush() {
    \Drupal::messenger()->addStatus(t('hook_cache_flush() invoked for update_script_test.module.'));
  }

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, $type): void {
    $new_info = \Drupal::state()->get('update_script_test.system_info_alter');
    if ($new_info) {
      if ($file->getName() == 'update_script_test') {
        $info = $new_info + $info;
      }
    }
  }

}
