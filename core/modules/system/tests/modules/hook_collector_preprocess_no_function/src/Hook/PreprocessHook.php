<?php

declare(strict_types=1);

namespace Drupal\hook_collector_preprocess_no_function\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Test Hook attribute for preprocess.
 */
class PreprocessHook {

  /**
   * Implements hook_cache_flush().
   */
  #[Hook('preprocess_test')]
  public function preprocess(): void {
    $GLOBALS['preprocess'] = 'preprocess';
  }

}
