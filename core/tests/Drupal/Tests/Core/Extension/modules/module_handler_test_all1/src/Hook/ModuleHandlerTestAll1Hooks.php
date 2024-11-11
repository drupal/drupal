<?php

declare(strict_types=1);

namespace Drupal\module_handler_test_all1\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for module_handler_test_all1.
 */
class ModuleHandlerTestAll1Hooks {

  #[Hook('order1')]
  #[Hook('order2')]
  public static function order(): void {
  }

}
