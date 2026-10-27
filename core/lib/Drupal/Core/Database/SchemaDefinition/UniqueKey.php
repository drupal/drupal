<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Describes a database unique key.
 */
final class UniqueKey extends KeyBase {

  /**
   * Constructor.
   *
   * @param string $name
   *   The unique key name.
   * @param list<KeyColumn|string> $columns
   *   A mix of key column specifiers, being KeyColumn objects or strings
   *   naming columns.
   */
  public function __construct(
    public readonly string $name,
    array $columns,
  ) {
    parent::__construct($columns, FALSE);
  }

}
