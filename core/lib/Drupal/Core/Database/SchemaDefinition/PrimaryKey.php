<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Describes a table's primary key.
 */
final class PrimaryKey extends KeyBase {

  /**
   * Constructor.
   *
   * @param list<KeyColumn|string> $columns
   *   A mix of key column specifiers, being KeyColumn objects, strings naming
   *   columns, or arrays of two elements, column name and length, specifying a
   *   prefix of the named column.
   */
  public function __construct(array $columns) {
    parent::__construct($columns, FALSE);
  }

}
