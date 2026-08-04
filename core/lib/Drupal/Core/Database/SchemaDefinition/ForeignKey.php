<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

use Drupal\Core\Database\Exception\SchemaDefinitionException;

/**
 * Describes a database foreign key.
 */
final class ForeignKey extends KeyBase {

  /**
   * The foreign key columns.
   *
   * @var list<KeyColumn>
   *   The list of KeyColumn objects of the foreign table.
   */
  public readonly array $foreignColumns;

  /**
   * Constructor.
   *
   * @param string $name
   *   The foreign key name.
   * @param string $foreignTable
   *   The foreign (referenced) table name.
   * @param list<KeyColumn|string> $columns
   *   A mix of key column specifiers, being KeyColumn objects, strings naming
   *   columns, or arrays of two elements, column name and length, specifying a
   *   prefix of the named column.
   * @param list<KeyColumn|string> $foreignColumns
   *   A mix of key column specifiers of the foreign (referenced) table, being
   *   KeyColumn objects, strings naming columns, or arrays of two elements,
   *   column name and length, specifying a prefix of the named column.
   */
  public function __construct(
    public readonly string $name,
    public readonly string $foreignTable,
    array $columns,
    array $foreignColumns,
  ) {
    parent::__construct($columns, FALSE);
    $this->foreignColumns = $this->buildKeyColumns($foreignColumns, FALSE);
    $this->validate();
  }

  /**
   * Validates the properties of the value object.
   *
   * @throws \Drupal\Core\Database\Exception\SchemaDefinitionException
   */
  private function validate(): void {

    if (count($this->columns) !== count($this->foreignColumns)) {
      throw new SchemaDefinitionException("Mismatching count of columns for the '{$this->name}' foreign key");
    }

  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    $match = [];
    for ($i = 0; $i < count($this->columns); $i++) {
      $localColumnName = $this->columns[$i]->name;
      $foreignColumnName = $this->foreignColumns[$i]->name;
      $match[$localColumnName] = $foreignColumnName;
    }
    return [
      'table' => $this->foreignTable,
      'columns' => $match,
    ];
  }

}
