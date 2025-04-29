<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_collector_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\bbb_hook_collector_test\Hook\TestHookAfterClassMethod as TestHookAfterClassMethodForAfter;

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
  #[Hook('custom_hook_test_hook_after_class_method',
    order: new OrderAfter(
      classesAndMethods: [[TestHookAfterClassMethodForAfter::class, 'hookAfterClassMethod']],
    )
  )]
  public static function hookAfterClassMethod(): string {
    return __METHOD__;
  }

}
