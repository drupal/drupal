<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\ccc_hook_order_test\Hook\CHooks;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ReorderHook;

/**
 * Provides hook implementations for testing the execution order of hooks.
 *
 * By default, these will be called in module order, which is predictable due to
 * the alphabetical module names.
 *
 * Two attributes are used to change the 'test_hook' implementations in module
 * ccc_hook_order_test. One is the ReorderHook attribute which is used to put
 * the \Drupal\ccc_hook_order_test\Hook\CHooks::testHookFirst() first. The other
 * is RemoveHook which is used to remove
 * \Drupal\ccc_hook_order_test\Hook\CHooks::testHookRemoved(). Both of those
 * attributes are declared in \Drupal\ddd_hook_order_test\Hook\DHooks.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testHookOrder()
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testBothParametersHookOrder()
 */
class AHooks {

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
   * This implementation changes its order to be first.
   */
  #[Hook('test_hook', order: Order::First)]
  public function testHookFirst(): string {
    return __METHOD__;
  }

  /**
   * Implements hook_test_hook().
   *
   * This implementation changes its order to be last.
   */
  #[Hook('test_hook', order: Order::Last)]
  public function testHookLast(): string {
    return __METHOD__;
  }

  /**
   * Implements hook_test_hook().
   *
   * This implementation changes its order to be after the hooks in module
   * bbb_hook_order_test.
   */
  #[Hook('test_hook', order: new OrderAfter(modules: ['bbb_hook_order_test']))]
  public function testHookAfterB(): string {
    return __METHOD__;
  }

  /**
   * Implements test_both_parameters_hook().
   *
   * This implementation changes its order to be after the hooks in module
   * bbb_hook_order_test and
   * \Drupal\ccc_hook_order_test\Hook\CHooks::testBothParametersHook().)
   */
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

  /**
   * Implements test_procedural_reorder().
   */
  #[ReorderHook('test_procedural_reorder', ProceduralCall::class, 'bbb_hook_order_test_test_procedural_reorder', Order::First)]
  #[RemoveHook('test_procedural_reorder', ProceduralCall::class, 'ccc_hook_order_test_test_procedural_reorder')]
  #[Hook('test_procedural_reorder')]
  public function testProceduralReorder(): string {
    return __METHOD__;
  }

}
