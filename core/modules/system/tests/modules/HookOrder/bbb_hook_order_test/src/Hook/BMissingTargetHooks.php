<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains hook implementations.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testReorderMissingTarget()
 */
class BMissingTargetHooks {

  #[Hook('test_ab_hook')]
  public function testABHookReorderedFirstByXyz(): string {
    return __METHOD__;
  }

  #[Hook('test_ab_hook')]
  public function testABHookRemovedByXyz(): string {
    return __METHOD__;
  }

  #[Hook('test_b_hook')]
  public function testBHook(): string {
    return __METHOD__;
  }

  #[Hook('test_b_hook')]
  public function testBHookReorderedFirstByXyz(): string {
    return __METHOD__;
  }

  #[Hook('test_b_hook')]
  public function testBHookRemovedByXyz(): string {
    return __METHOD__;
  }

  #[Hook('test_unrelated_hook')]
  public function testUnrelatedHook(): string {
    return __METHOD__;
  }

}
