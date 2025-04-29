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
class TestHookFirst {

  /**
   * This pair tests OrderFirst.
   */
  #[Hook('custom_hook_test_hook_first')]
  public function hookFirst(): string {
    // This should be run second, there is another hook reordering before this.
    return __METHOD__;
  }

}
