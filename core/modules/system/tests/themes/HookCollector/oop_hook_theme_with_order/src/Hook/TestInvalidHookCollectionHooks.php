<?php

declare(strict_types=1);

namespace Drupal\oop_hook_theme_with_order\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;

/**
 * Contains hooks that will throw exceptions during collection.
 */
class TestInvalidHookCollectionHooks {

  #[Hook('test_hook_alter', order: Order::First)]
  public function testHook(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
