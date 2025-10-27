<?php

declare(strict_types=1);

namespace Drupal\oop_hook_theme_with_reorder\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;

/**
 * Contains hooks that will throw exceptions during collection.
 */
class TestInvalidHookCollectionHooks {

  #[Hook('test_hook_alter')]
  #[ReorderHook('test_hook_alter', self::class, 'testHook', Order::First)]
  public function testHook(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
