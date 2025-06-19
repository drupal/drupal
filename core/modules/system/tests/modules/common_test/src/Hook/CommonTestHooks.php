<?php

declare(strict_types=1);

namespace Drupal\common_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for common_test.
 */
class CommonTestHooks {

  /**
   * Implements hook_TYPE_alter().
   */
  #[Hook('drupal_alter_alter')]
  public function drupalAlterAlter(&$data, &$arg2 = NULL, &$arg3 = NULL): void {
    // Alter first argument.
    if (is_array($data)) {
      $data['foo'] = 'Drupal';
    }
    elseif (is_object($data)) {
      $data->foo = 'Drupal';
    }
    // Alter second argument, if present.
    if (isset($arg2)) {
      if (is_array($arg2)) {
        $arg2['foo'] = 'Drupal';
      }
      elseif (is_object($arg2)) {
        $arg2->foo = 'Drupal';
      }
    }
    // Try to alter third argument, if present.
    if (isset($arg3)) {
      if (is_array($arg3)) {
        $arg3['foo'] = 'Drupal';
      }
      elseif (is_object($arg3)) {
        $arg3->foo = 'Drupal';
      }
    }
  }

  /**
   * Implements hook_TYPE_alter().
   *
   * This is to verify that
   * \Drupal::moduleHandler()->alter([TYPE1, TYPE2], ...) allows
   * hook_module_implements_alter() to affect the order in which module
   * implementations are executed.
   */
  #[Hook('drupal_alter_foo_alter', module: 'block')]
  public function blockDrupalAlterFooAlter(&$data, &$arg2 = NULL, &$arg3 = NULL): void {
    $data['foo'] .= ' block';
  }

  /**
   * Implements hook_cron().
   *
   * System module should handle if a module does not catch an exception and
   * keep cron going.
   *
   * @see common_test_cron_helper()
   */
  #[Hook('cron')]
  public function cron(): void {
    throw new \Exception('Uncaught exception');
  }

}
