<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class TestHookLast {

  /**
   * This pair tests OrderLast.
   */
  #[Hook('custom_hook_test_hook_last')]
  public function hookLast(): string {
    return __METHOD__;
  }

}
