<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;

/**
 * Contains alter hook implementations.
 *
 * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest::testReorderCrossHookAlter()
 */
class ACrossHookReorderAlter {

  /**
   * Implements hook_test_cross_hook_reorder_base_alter().
   */
  #[Hook('test_cross_hook_reorder_base_alter', order: Order::Last)]
  public function baseAlterLast(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_cross_hook_reorder_base_alter().
   *
   * This method implements the base alter hook, and has an Order::Last rule.
   * In addition, it is targeted by a #[ReorderHook] for the subtype alter hook.
   *
   * @see self::subtypeAlterLast()
   */
  #[ReorderHook('test_cross_hook_reorder_subtype_alter', self::class, 'baseAlterLastAlsoIfSubtype', Order::Last)]
  #[Hook('test_cross_hook_reorder_base_alter', order: Order::Last)]
  public function baseAlterLastAlsoIfSubtype(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_cross_hook_reorder_base_alter().
   */
  #[ReorderHook('test_cross_hook_reorder_subtype_alter', self::class, 'baseAlterLastIfSubtype', Order::Last)]
  #[Hook('test_cross_hook_reorder_base_alter')]
  public function baseAlterLastIfSubtype(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements the subtype alter hook.
   *
   * In a call to ->alter(['..base', '..subtype'], ..), this method wants to be
   * called after most other implementations, but not after
   * ::baseAlterLastAlsoIfSubtype().
   *
   * To achieve this, it has a #[ReorderHook] that targets
   * ::baseAlterLastAlsoIfSubtype() in context of the subtype hook, meant to
   * reinforce the Order::Last from that base hook implementation.
   */
  #[Hook('test_cross_hook_reorder_subtype_alter', order: Order::Last)]
  public function subtypeAlterLast(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_cross_hook_reorder_subtype_alter().
   */
  #[ReorderHook('test_cross_hook_reorder_base_alter', self::class, 'subtypeAlterLastIfBaseHook', Order::Last)]
  #[Hook('test_cross_hook_reorder_subtype_alter')]
  public function subtypeAlterLastIfBaseHook(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements hook_test_cross_hook_reorder_base_alter().
   */
  #[Hook('test_cross_hook_reorder_base_alter')]
  public function baseAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

  /**
   * Implements test_cross_hook_reorder_subtype_alter().
   */
  #[Hook('test_cross_hook_reorder_subtype_alter')]
  public function subtypeAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
