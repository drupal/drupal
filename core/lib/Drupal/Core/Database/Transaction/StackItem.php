<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Transaction;

/**
 * A value object for items on the transaction stack.
 */
final class StackItem {

  /**
   * Constructor.
   *
   * @param string $name
   *   The name of the transaction.
   * @param StackItemType $type
   *   The stack item type.
   */
  public function __construct(
    public readonly string $name,
    public readonly StackItemType $type,
  ) {
  }

}
