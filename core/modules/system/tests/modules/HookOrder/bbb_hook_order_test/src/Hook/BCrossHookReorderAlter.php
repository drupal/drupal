<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;

/**
 * Contains alter hook implementations.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest::testReorderCrossHookAlter()
 */
class BCrossHookReorderAlter {

  /**
   * Implements hook_test_cross_hook_reorder_base_alter().
   */
  #[Hook('test_cross_hook_reorder_base_alter', order: Order::Last)]
  public function baseAlterLast(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_cross_hook_reorder_subtype_alter().
   */
  #[Hook('test_cross_hook_reorder_subtype_alter')]
  public function subtypeAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_cross_hook_reorder_base_alter().
   */
  #[Hook('test_cross_hook_reorder_base_alter')]
  public function baseAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
