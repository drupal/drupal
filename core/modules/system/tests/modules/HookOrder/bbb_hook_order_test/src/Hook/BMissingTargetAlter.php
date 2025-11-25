<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains alter hook implementations.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest::testReorderAlterMissingTarget()
 */
class BMissingTargetAlter {

  /**
   * Implements hook_test_ab_alter().
   */
  #[Hook('test_ab_alter')]
  public function testABAlterReorderedFirstByXyz(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_ab_alter().
   */
  #[Hook('test_ab_alter')]
  public function testABAlterRemovedByXyz(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_b_alter().
   */
  #[Hook('test_b_alter')]
  public function testBAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_b_alter().
   */
  #[Hook('test_b_alter')]
  public function testBAlterReorderedFirstByXyz(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_b_alter().
   */
  #[Hook('test_b_alter')]
  public function testBAlterRemovedByXyz(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_b_subtype_alter().
   */
  #[Hook('test_b_subtype_alter')]
  public function testBSubtypeAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
