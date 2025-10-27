<?php

declare(strict_types=1);

namespace Drupal\oop_hook_theme\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains hooks for testing hook collection.
 */
class TestHookCollectionHooks {

  #[Hook('test_hook_alter')]
  public function testHookAlter(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
