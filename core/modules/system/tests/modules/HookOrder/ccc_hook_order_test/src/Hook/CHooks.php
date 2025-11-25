<?php

declare(strict_types=1);

namespace Drupal\ccc_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;

/**
 * Provides hook implementations for testing the execution order of hooks.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testHookOrder()
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testBothParametersHookOrder()
 */
class CHooks {

  /**
   * Implements hook_test_hook().
   *
   * This implementation has no ordering modifications.
   */
  #[Hook('test_hook')]
  public function testHook(): string {
    return __METHOD__;
  }

  /**
   * Implements hook_test_hook().
   *
   * This implementation is modified to be first.
   */
  #[Hook('test_hook', order: Order::First)]
  public function testHookFirst(): string {
    return __METHOD__;
  }

  /**
   * Implements hook_test_hook().
   *
   * This implementation is modified in class
   * \Drupal\ddd_hook_order_test\Hook\DHooks to be first.
   *
   * @see \Drupal\ddd_hook_order_test\Hook\DHooks
   */
  #[Hook('test_hook')]
  public function testHookReorderFirst(): string {
    return __METHOD__;
  }

  /**
   * Implements hook_test_hook().
   *
   * This implementation is removed in class
   * \Drupal\ddd_hook_order_test\Hook\DHooks.
   *
   * @see \Drupal\ddd_hook_order_test\Hook\DHooks
   */
  #[Hook('test_hook')]
  public function testHookRemoved(): string {
    return __METHOD__;
  }

  /**
   * Implements hook_test_hook().
   *
   * This implementation is modified in
   * \Drupal\aaa_hook_order_test\Hook\AHooks::testBothParametersHook using the
   * OrderAfter attribute.
   */
  #[Hook('test_both_parameters_hook')]
  public function testBothParametersHook(): string {
    return __METHOD__;
  }

}
