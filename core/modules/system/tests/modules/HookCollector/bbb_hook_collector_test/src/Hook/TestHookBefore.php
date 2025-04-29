<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderBefore;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class TestHookBefore {

  /**
   * This pair tests OrderBefore.
   */
  #[Hook('custom_hook_test_hook_before', order: new OrderBefore(['aaa_hook_collector_test']))]
  public function hookBefore(): string {
    return __METHOD__;
  }

}
