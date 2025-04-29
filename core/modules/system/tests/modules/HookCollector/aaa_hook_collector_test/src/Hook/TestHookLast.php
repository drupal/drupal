<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;

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
  #[Hook('custom_hook_test_hook_last', order: Order::Last)]
  public function hookLast(): string {
    // This should be run after.
    return __METHOD__;
  }

}
