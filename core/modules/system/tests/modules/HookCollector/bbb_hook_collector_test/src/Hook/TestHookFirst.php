<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;

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
  #[Hook('custom_hook_test_hook_first', order: Order::First)]
  public function hookFirst(): string {
    return __METHOD__;
  }

}
