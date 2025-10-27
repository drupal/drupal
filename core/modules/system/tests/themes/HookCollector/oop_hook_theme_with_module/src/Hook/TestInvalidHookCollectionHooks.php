<?php

declare(strict_types=1);

namespace Drupal\oop_hook_theme_with_module\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains hooks that will throw exceptions during collection.
 */
class TestInvalidHookCollectionHooks {

  #[Hook('test_hook_alter', module: 'test')]
  public function testHook(array &$calls): void {
    $calls[] = __METHOD__;
  }

}
