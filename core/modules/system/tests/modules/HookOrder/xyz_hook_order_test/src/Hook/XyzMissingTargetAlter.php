<?php

declare(strict_types=1);

namespace Drupal\xyz_hook_order_test\Hook;

use Drupal\aaa_hook_order_test\Hook\AMissingTargetAlter;
use Drupal\bbb_hook_order_test\Hook\BMissingTargetAlter;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;

/**
 * This class contains attributes to reorder or remove alter implementations.
 *
 * The idea behind the hook names:
 *   - hook_test_ab_alter() has implementations in modules A and B.
 *   - hook_test_b_alter() has implementations only in module B.
 *     As a consequence, it has no implementations if module B is not installed.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest::testReorderAlterMissingTarget()
 */
class XyzMissingTargetAlter {

  /**
   * Hook order attributes that target possibly non-existing alter methods.
   *
   * The targeted methods only exist if module B is installed.
   */
  #[ReorderHook('test_ab_alter', BMissingTargetAlter::class, 'testABAlterReorderedFirstByXyz', Order::First)]
  #[RemoveHook('test_ab_alter', BMissingTargetAlter::class, 'testABAlterRemovedByXyz')]
  public function targetABAlter(): void {}

  /**
   * Hook order attributes that target a hook with possibly no implementations.
   *
   * The target hook has implementations only if module B is installed.
   */
  #[ReorderHook('test_b_alter', BMissingTargetAlter::class, 'testBAlterReorderedFirstByXyz', Order::First)]
  #[RemoveHook('test_b_alter', BMissingTargetAlter::class, 'testBAlterRemovedByXyz')]
  public function targetBAlter(): void {}

  /**
   * Hook order attributes where the target method implements a different hook.
   *
   * This scenario is special for alter hooks, when the alter types for both
   * hooks are passed to ->alter().
   */
  #[ReorderHook('test_b_subtype_alter', AMissingTargetAlter::class, 'testASupertypeAlterReorderedFirstForBSubtypeByXyz', Order::First)]
  #[RemoveHook('test_b_subtype_alter', AMissingTargetAlter::class, 'testASupertypeAlterRemovedForBSubtypeByXyz')]
  public function targetASupertypeAlterForBSubtypeAlter(): void {}

}
