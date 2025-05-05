<?php

declare(strict_types = 1);

namespace Drupal\Core\Hook\OrderOperation;

/**
 * Moves one listener to be called before or after other listeners.
 *
 * @internal
 */
class BeforeOrAfter extends OrderOperation {

  /**
   * Constructor.
   *
   * @param string $identifier
   *   Identifier of the implementation to move to a new position. The format
   *   is the class followed by "::" then the method name. For example,
   *   "Drupal\my_module\Hook\MyModuleHooks::methodName".
   * @param list<string> $modulesToOrderAgainst
   *   Module names of listeners to order against.
   * @param list<string> $identifiersToOrderAgainst
   *   Identifiers of listeners to order against.
   *   The format is "$class::$method".
   * @param bool $isAfter
   *   TRUE, if the listener to move should be moved after the listener to order
   *   against, FALSE if it should be moved before.
   */
  public function __construct(
    protected readonly string $identifier,
    protected readonly array $modulesToOrderAgainst,
    protected readonly array $identifiersToOrderAgainst,
    protected readonly bool $isAfter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function apply(array &$identifiers, array $module_finder): void {
    assert(array_is_list($identifiers));
    $index = array_search($this->identifier, $identifiers);
    if ($index === FALSE) {
      // Nothing to reorder.
      return;
    }
    $identifiers_to_order_against = $this->identifiersToOrderAgainst;
    if ($this->modulesToOrderAgainst) {
      $identifiers_to_order_against = [
        ...$identifiers_to_order_against,
        ...array_keys(array_intersect($module_finder, $this->modulesToOrderAgainst)),
      ];
    }
    $indices_to_order_against = array_keys(array_intersect($identifiers, $identifiers_to_order_against));
    if ($indices_to_order_against === []) {
      return;
    }
    if ($this->isAfter) {
      $max_index_to_order_against = max($indices_to_order_against);
      if ($index >= $max_index_to_order_against) {
        // The element is already after the other elements.
        return;
      }
      array_splice($identifiers, $max_index_to_order_against + 1, 0, $this->identifier);
      // Remove the element after splicing.
      unset($identifiers[$index]);
      $identifiers = array_values($identifiers);
    }
    else {
      $min_index_to_order_against = min($indices_to_order_against);
      if ($index <= $min_index_to_order_against) {
        // The element is already before the other elements.
        return;
      }
      // Remove the element before splicing.
      unset($identifiers[$index]);
      array_splice($identifiers, $min_index_to_order_against, 0, $this->identifier);
    }
  }

}
