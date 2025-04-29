<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class TestHookBefore {

  /**
   * This pair tests OrderBefore.
   */
  #[Hook('custom_hook_test_hook_before')]
  public function hookBefore(): string {
    // This should be run second, there is another hook reordering before this.
    return __METHOD__;
  }

}
