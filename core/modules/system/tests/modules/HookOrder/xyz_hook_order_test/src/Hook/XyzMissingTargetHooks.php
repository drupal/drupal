<?php

declare(strict_types=1);

namespace Drupal\xyz_hook_order_test\Hook;

use Drupal\aaa_hook_order_test\Hook\AMissingTargetHooks;
use Drupal\bbb_hook_order_test\Hook\BMissingTargetHooks;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;

/**
 * This class contains attributes to reorder or remove hook implementations.
 *
 * When module 'bbb_hook_order_test' is not installed, but 'xyz_hook_order_test'
 * is installed, these attributes will target non-existing implementations.
 *
 * The idea behind the hook names:
 *   - hook_test_ab_hook() has implementations in modules A and B.
 *   - hook_test_b_hook() has implementations only in module B. As a
 *     consequence, it has no implementations if module B is not installed.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testReorderMissingTarget()
 */
class XyzMissingTargetHooks {

  /**
   * Hook order attributes that target possibly non-existing implementations.
   *
   * The targeted methods only exist if module B is installed.
   */
  #[ReorderHook('test_ab_hook', BMissingTargetHooks::class, 'testABHookReorderedFirstByXyz', Order::First)]
  #[RemoveHook('test_ab_hook', BMissingTargetHooks::class, 'testABHookRemovedByXyz')]
  public function targetABHook(): void {}

  /**
   * Hook order attributes that target a hook with possibly no implementations.
   *
   * The target hook has implementations only if module B is installed.
   */
  #[ReorderHook('test_b_hook', BMissingTargetHooks::class, 'testBHookReorderedFirstByXyz', Order::First)]
  #[RemoveHook('test_b_hook', BMissingTargetHooks::class, 'testBHookRemovedByXyz')]
  public function targetBHook(): void {}

  /**
   * Hook order attributes where the target method implements a different hook.
   *
   * For non-alter hooks, such attributes have no effect.
   *
   * This scenario can be relevant if the target method is registered for
   * different hooks in different versions of the target module.
   */
  #[ReorderHook('test_b_hook', AMissingTargetHooks::class, 'testUnrelatedHookReorderedLastForHookB', Order::Last)]
  #[RemoveHook('test_b_hook', AMissingTargetHooks::class, 'testUnrelatedHookRemovedForHookB')]
  public function targetUnrelatedHookForBHook(): void {}

}
