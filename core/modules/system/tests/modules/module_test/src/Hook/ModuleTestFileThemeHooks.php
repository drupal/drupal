<?php

declare(strict_types=1);

namespace Drupal\module_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for module_test.
 */
class ModuleTestFileThemeHooks {

  /**
   * Implements hook_test_hook().
   */
  #[Hook('test_hook')]
  public function testHook(): array {
    return [
      'module_test' => 'success!',
    ];
  }

  /**
   * Implements hook_test_reset_implementations_hook().
   */
  #[Hook('test_reset_implementations_hook')]
  public function testResetImplementationsHook(): string {
    return 'module_test_test_reset_implementations_hook';
  }

  /**
   * Implements hook_test_reset_implementations_alter().
   */
  #[Hook('test_reset_implementations_alter')]
  public function testResetImplementationsAlter(array &$data): void {
    $data[] = 'module_test_test_reset_implementations_alter';
  }

}
