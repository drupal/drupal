<?php

declare(strict_types=1);

namespace Drupal\oop_hook_theme_with_remove\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\RemoveHook;

/**
 * Contains hooks that will throw exceptions during collection.
 */
class TestInvalidHookCollectionHooks {

  #[Hook('test_hook_alter')]
  #[RemoveHook('test_hook_alter', self::class, 'testHook')]
  public function testHook(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
