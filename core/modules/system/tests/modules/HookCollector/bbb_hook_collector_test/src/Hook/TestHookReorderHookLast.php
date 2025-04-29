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
class TestHookReorderHookLast {

  /**
   * This pair tests ReorderHook.
   */
  #[Hook('custom_hook_override', order: Order::First)]
  public function customHookOverride(): string {
    // This normally would run second.
    // We override that order here with Order::First.
    // We override, that order in aaa_hook_collector_test with
    // ReorderHook.
    return __METHOD__;
  }

}
