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
class TestHookAfter {

  /**
   * This pair tests OrderAfter.
   */
  #[Hook('custom_hook_test_hook_after', order: new OrderAfter(['bbb_hook_collector_test']))]
  public function hookAfter(): string {
    return __METHOD__;
  }

}
