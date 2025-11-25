<?php

declare(strict_types=1);

namespace Drupal\ccc_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest
 */
class CAlterHooks {

  /**
   * Implements hook_test_alter().
   *
   * This implementation has no ordering modifications.
   */
  #[Hook('test_alter')]
  public function testAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_subtype_alter().
   *
   * This implementation has no ordering modifications.
   */
  #[Hook('test_subtype_alter')]
  public function testSubtypeAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
