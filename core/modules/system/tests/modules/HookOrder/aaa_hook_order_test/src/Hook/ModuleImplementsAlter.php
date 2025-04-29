<?php

declare(strict_types=1);

namespace Drupal\aaa_hook_order_test\Hook;

/**
 * Contains a replaceable callback for hook_module_implements_alter().
 */
class ModuleImplementsAlter {

  /**
   * Callback for hook_module_implements_alter().
   *
   * @var ?\Closure
   * @phpstan-var (\Closure(array<string, string|false>&, string): void)|null
   */
  private static ?\Closure $callback = NULL;

  /**
   * Sets a callback for hook_module_implements_alter().
   *
   * @param ?\Closure $callback
   *   Callback to set, or NULL to unset.
   *
   * @phpstan-param (\Closure(array<string, string|false>&, string): void)|null $callback
   */
  public static function set(?\Closure $callback): void {
    self::$callback = $callback;
  }

  /**
   * Invokes the registered callback.
   *
   * @param array<string, string|false> $implementations
   *   The implementations, as "group" by module name.
   * @param string $hook
   *   The hook.
   */
  public static function call(array &$implementations, string $hook): void {
    if (self::$callback === NULL) {
      return;
    }
    (self::$callback)($implementations, $hook);
  }

}
