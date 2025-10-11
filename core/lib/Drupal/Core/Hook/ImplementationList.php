<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

/**
 * Contains the ordered list of implementations for a hook.
 *
 * Also contains information about module names.
 *
 * @internal
 */
class ImplementationList {

  /**
   * Constructor.
   *
   * @param list<callable> $listeners
   *   List of hook implementation callbacks.
   * @param list<string> $modules
   *   The module name associated with each hook implementation.
   *   This must have the same keys as $listeners.
   */
  public function __construct(
    public readonly array $listeners,
    public readonly array $modules,
  ) {
    assert(array_is_list($listeners));
    assert(array_is_list($modules));
    assert(count($listeners) === count($modules));
    assert(array_filter($listeners, is_callable(...)) === $listeners);
    assert(array_filter($modules, is_string(...)) === $modules, (new \Exception())->getTraceAsString());
  }

  /**
   * Iterates over listeners, using module names as keys.
   *
   * @return \Iterator<string, callable>
   *   Iterator of listeners by module.
   *   This allows the same module to occur more than once.
   */
  public function iterateByModule(): \Iterator {
    foreach ($this->listeners as $index => $listener) {
      yield $this->modules[$index] => $listener;
    }
  }

  /**
   * Gets listeners for a specific module.
   *
   * @param string $module
   *   Module name.
   *
   * @return list<callable>
   *   Listeners for that module.
   */
  public function getForModule(string $module): array {
    return array_values(array_intersect_key(
      $this->listeners,
      array_intersect($this->modules, [$module]),
    ));
  }

  /**
   * Checks whether the list has any implementations.
   *
   * @return bool
   *   TRUE if it has implementations, FALSE if it is empty.
   */
  public function hasImplementations(): bool {
    return $this->listeners !== [];
  }

  /**
   * Checks whether the list has any implementations for specific modules.
   *
   * @param list<string> $modules
   *   Modules for which to check.
   *
   * @return bool
   *   TRUE if it has implementations for any of the modules, FALSE if not.
   */
  public function hasImplementationsForModules(array $modules): bool {
    return (bool) array_intersect($this->modules, $modules);
  }

}
