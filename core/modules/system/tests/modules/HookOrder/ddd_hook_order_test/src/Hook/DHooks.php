<?php

declare(strict_types=1);

namespace Drupal\ddd_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;
use Drupal\ccc_hook_order_test\Hook\CHooks;

/**
 * Provides hook implementations for testing the execution order of hooks.
 *
 * By default, these will be called in module order, which is predictable due to
 * the alphabetical module names.
 *
 * Two attributes are used to change the execution order of the 'test_hook'
 * implementations in module ccc_hook_order_test. The ReorderHook attribute is
 * used to put the \Drupal\ccc_hook_order_test\Hook\CHooks::testHookFirst()
 * first and the \Drupal\ccc_hook_order_test\Hook\CHooks::testHookRemoved()
 * attribute is used to remove testHookRemoved.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testHookOrder()
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testSparseHookOrder()
 */
#[ReorderHook('test_hook', CHooks::class, 'testHookReorderFirst', Order::First)]
#[RemoveHook('test_hook', CHooks::class, 'testHookRemoved')]
class DHooks {

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
   * This implementation has no ordering modifications.
   */
  #[Hook('sparse_test_hook')]
  public function sparseTestHook(): string {
    return __METHOD__;
  }

}
