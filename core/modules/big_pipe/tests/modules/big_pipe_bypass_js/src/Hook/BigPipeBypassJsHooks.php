<?php

declare(strict_types=1);

namespace Drupal\big_pipe_bypass_js\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for big_pipe_bypass_js.
 */
class BigPipeBypassJsHooks {

  /**
   * Implements hook_library_info_alter().
   *
   * Disables Big Pipe JavaScript by removing the js file from the library.
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    if ($extension === 'big_pipe') {
      unset($libraries['big_pipe']['js']);
    }
  }

}
