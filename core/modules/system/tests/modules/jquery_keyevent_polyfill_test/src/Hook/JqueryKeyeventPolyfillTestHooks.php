<?php

declare(strict_types=1);

namespace Drupal\jquery_keyevent_polyfill_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for jquery_keyevent_polyfill_test.
 */
class JqueryKeyeventPolyfillTestHooks {

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $module): void {
    if ($module == 'core' && isset($libraries['jquery'])) {
      $libraries['jquery']['dependencies'][] = 'jquery_keyevent_polyfill_test/jquery.keyevent.polyfill';
    }
  }

}
