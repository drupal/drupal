<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Describes a database index.
 */
final class Index extends KeyBase {

  /**
   * Constructor.
   *
   * @param string $name
   *   The index name.
   * @param list<KeyColumn|string|array{0:string, 1:int}> $columns
   *   A mix of key column specifiers, being KeyColumn objects, strings naming
   *   columns, or arrays of two elements, column name and length, specifying a
   *   prefix of the named column.
   */
  public function __construct(
    public readonly string $name,
    array $columns,
  ) {
    parent::__construct($columns, TRUE);
  }

}
