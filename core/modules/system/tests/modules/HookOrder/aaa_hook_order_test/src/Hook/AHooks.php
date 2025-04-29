<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\ccc_hook_order_test\Hook\CHooks;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class AHooks {

  #[Hook('test_hook')]
  public function testHook(): string {
    return __METHOD__;
  }

  #[Hook('test_hook', order: Order::First)]
  public function testHookFirst(): string {
    return __METHOD__;
  }

  #[Hook('test_hook', order: Order::Last)]
  public function testHookLast(): string {
    return __METHOD__;
  }

  #[Hook('test_hook', order: new OrderAfter(modules: ['bbb_hook_order_test']))]
  public function testHookAfterB(): string {
    return __METHOD__;
  }

  #[Hook(
    'test_both_parameters_hook',
    order: new OrderAfter(
      modules: ['bbb_hook_order_test'],
      classesAndMethods: [[CHooks::class, 'testBothParametersHook']]
    )
  )]
  public function testBothParametersHook(): string {
    return __METHOD__;
  }

}
