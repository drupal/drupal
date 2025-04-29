<?php

declare(strict_types=1);

namespace Drupal\ddd_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;
use Drupal\ccc_hook_order_test\Hook\CHooks;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names.
 */
#[ReorderHook('test_hook', CHooks::class, 'testHookReorderFirst', Order::First)]
#[RemoveHook('test_hook', CHooks::class, 'testHookRemoved')]
class DHooks {

  #[Hook('test_hook')]
  public function testHook(): string {
    return __METHOD__;
  }

  #[Hook('sparse_test_hook')]
  public function sparseTestHook(): string {
    return __METHOD__;
  }

}
