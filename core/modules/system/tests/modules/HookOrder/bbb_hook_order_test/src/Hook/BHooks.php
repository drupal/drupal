<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Provides hook implementations for testing the execution order of hooks.
 *
 * By default, these will be called in module order, which is predictable due to
 * the alphabetical module names.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testHookOrder()
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testSparseHookOrder()
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testBothParametersHookOrder()
 */
class BHooks {

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

  /**
   * Implements hook_test_hook().
   *
   * The order of this implementation is modified in
   * \Drupal\aaa_hook_order_test\Hook\AHooks::testBothParametersHook() to be
   * before \Drupal\aaa_hook_order_test\Hook\AHooks::testBothParametersHook().
   */
  #[Hook('test_both_parameters_hook')]
  public function testBothParametersHook(): string {
    return __METHOD__;
  }

}
