<?php

declare(strict_types=1);

namespace Drupal\ccc_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class CHooks {

  #[Hook('test_hook')]
  public function testHook(): string {
    return __METHOD__;
  }

  #[Hook('test_hook', order: Order::First)]
  public function testHookFirst(): string {
    return __METHOD__;
  }

  /**
   * This implementation is reordered from elsewhere.
   *
   * @see \Drupal\ddd_hook_order_test\Hook\DHooks
   */
  #[Hook('test_hook')]
  public function testHookReorderFirst(): string {
    return __METHOD__;
  }

  /**
   * This implementation is removed from elsewhere.
   *
   * @see \Drupal\ddd_hook_order_test\Hook\DHooks
   */
  #[Hook('test_hook')]
  public function testHookRemoved(): string {
    return __METHOD__;
  }

  #[Hook('test_both_parameters_hook')]
  public function testBothParametersHook(): string {
    return __METHOD__;
  }

}
