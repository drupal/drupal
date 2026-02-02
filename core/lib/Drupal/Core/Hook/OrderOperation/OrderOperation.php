<?php

declare(strict_types = 1);

namespace Drupal\Core\Hook\OrderOperation;

/**
 * Base class for order operations.
 */
abstract class OrderOperation {

  /**
   * Changes the order of a list of hook implementations.
   *
   * @param list<string> $identifiers
   *   Hook implementation identifiers, as "$class::$method", to be changed by
   *   reference.
   *   The order operation must make sure that the array remains a list, and
   *   that the values are the same as before.
   * @param array<string, string> $module_finder
   *   Lookup map to find a module name for each implementation.
   *   This may contain more entries than $identifiers.
   */
  abstract public function apply(array &$identifiers, array $module_finder): void;

  /**
   * Gets an identifier for the target implementation.
   *
   * This is used in HookCollectorPass, to remove order operations where the
   * target implementation does not exist at all, or is not registered for the
   * hook that the order operation targets.
   *
   * The hook implementation identifier, as "$class::$method", to be changed by.
   *
   * @return string
   *   Identifier for the implementation that should be moved to a new position.
   *   The format is the class followed by '::', then the method name.
   *   For example "Drupal\my_module\Hook\MyModuleHooks::methodName".
   */
  abstract public function identify(): string;

  /**
   * Converts the operation to a structure that can be stored in the container.
   *
   * @return array
   *   Packed operation.
   */
  final public function pack(): array {
    $is_before_or_after = match(get_class($this)) {
      BeforeOrAfter::class => TRUE,
      FirstOrLast::class => FALSE,
    };
    return [$is_before_or_after, get_object_vars($this)];
  }

  /**
   * Converts the stored operation to objects that can apply ordering rules.
   *
   * @param array $packed_operation
   *   Packed operation.
   *
   * @return self
   *   Unpacked operation.
   */
  final public static function unpack(array $packed_operation): self {
    [$is_before_or_after, $args] = $packed_operation;
    $class = $is_before_or_after ? BeforeOrAfter::class : FirstOrLast::class;
    return new $class(...$args);
  }

}
