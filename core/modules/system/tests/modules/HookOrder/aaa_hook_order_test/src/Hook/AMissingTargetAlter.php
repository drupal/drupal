<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Provides hook implementations for testing the alteration of hooks.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest::testReorderAlterMissingTarget()
 */
class AMissingTargetAlter {

  /**
   * Implements hook_test_ab_alter().
   */
  #[Hook('test_ab_alter')]
  public function testABAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_testASupertypeAlter().
   */
  #[Hook('test_a_supertype_alter')]
  public function testASupertypeAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_a_supertype_alter().
   */
  #[Hook('test_a_supertype_alter')]
  public function testASupertypeAlterReorderedFirstForBSubtypeByXyz(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_a_supertype_alter().
   */
  #[Hook('test_a_supertype_alter')]
  public function testASupertypeAlterRemovedForBSubtypeByXyz(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
