<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains hook implementations.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testReorderMissingTarget()
 */
class AMissingTargetHooks {

  #[Hook('test_ab_hook')]
  public function testABHook(): string {
    return __METHOD__;
  }

  #[Hook('test_unrelated_hook')]
  public function testUnrelatedHookReorderedLastForHookB(): string {
    return __METHOD__;
  }

  #[Hook('test_unrelated_hook')]
  public function testUnrelatedHookRemovedForHookB(): string {
    return __METHOD__;
  }

}
