<?php

declare(strict_types=1);

namespace Drupal\ddd_hook_order_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * This class contains hook implementations.
 *
 * By default, these will be called in module order, which is predictable due
 * to the alphabetical module names.
 */
class DAlterHooks {

  #[Hook('test_alter')]
  public function testAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

  #[Hook('test_subtype_alter')]
  public function testSubtypeAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
