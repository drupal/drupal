<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest
 */
class AAlterHooks {

  /**
   * Implements hook_test_alter().
   *
   * This implementation changes its order to be after the hooks in module
   * 'ccc_hook_order_test'.
   */
  #[Hook('test_alter', order: new OrderAfter(modules: ['ccc_hook_order_test']))]
  public function testAlterAfterC(array &$calls): void {
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
