<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\bbb_hook_collector_test\Hook\TestHookReorderHookLast;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class TestHookReorderHookFirst {

  /**
   * This pair tests ReorderHook.
   */
  #[Hook('custom_hook_override')]
  #[ReorderHook(
    'custom_hook_override',
    class: TestHookReorderHookLast::class,
    method: 'customHookOverride',
    order: new OrderAfter(
      classesAndMethods: [[TestHookReorderHookFirst::class, 'customHookOverride']],
    )
  )]
  public function customHookOverride(): string {
    // This normally would run first.
    // We override that order in hook_order_second_alphabetically.
    // We override, that order here with ReorderHook.
    return __METHOD__;
  }

}
