<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class TestHookAfterClassMethod {

  /**
   * This pair tests OrderAfter with a passed class and method.
   */
  #[Hook('custom_hook_test_hook_after_class_method')]
  public static function hookAfterClassMethod(): string {
    // This should be run first since another hook overrides the natural order.
    return __METHOD__;
  }

}
