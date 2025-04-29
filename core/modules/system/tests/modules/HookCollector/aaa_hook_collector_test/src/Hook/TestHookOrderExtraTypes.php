<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class TestHookOrderExtraTypes {

  /**
   * This pair tests OrderAfter with ExtraTypes.
   */
  #[Hook('custom_hook_extra_types1_alter',
    order: new OrderAfter(
      modules: ['bbb_hook_collector_test'],
    )
  )]
  public function customHookExtraTypes(array &$calls): void {
    // This should be run after.
    $calls[] = __METHOD__;
  }

}
