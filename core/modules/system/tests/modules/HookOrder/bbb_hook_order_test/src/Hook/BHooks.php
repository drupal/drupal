<?php

declare(strict_types=1);

namespace Drupal\bbb_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names. Some of the implementations are reordered
 * using order attributes.
 */
class BHooks {

  #[Hook('test_hook')]
  public function testHook(): string {
    return __METHOD__;
  }

  #[Hook('sparse_test_hook')]
  public function sparseTestHook(): string {
    return __METHOD__;
  }

  #[Hook('test_both_parameters_hook')]
  public function testBothParametersHook(): string {
    return __METHOD__;
  }

}
