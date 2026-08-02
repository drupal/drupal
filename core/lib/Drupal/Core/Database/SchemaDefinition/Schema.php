<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Database\Exception\SchemaDefinitionException;

/**
 * Describes a database schema as a collection of database objects.
 */
final class Schema implements SchemaDefinitionInterface {

  /**
   * Constructor.
   *
   * @param SchemaDefinitionType $type
   *   The type of Drupal feature providing the schema.
   * @param string $name
   *   The schema name.
   * @param list<Table> $tables
   *   A list of Table objects.
   */
  public function __construct(
    public readonly SchemaDefinitionType $type,
    public readonly string $name,
    public readonly array $tables,
  ) {
    $this->validate();
  }

  /**
   * Validates the properties of the value object.
   *
   * @throws \Drupal\Core\Database\Exception\SchemaDefinitionException
   */
  private function validate(): void {

    if ($this->tables && !Inspector::assertAllObjects($this->tables, Table::class)) {
      throw new SchemaDefinitionException("All members of the 'tables' argument of a Schema must be Table objects");
    }

  }

  /**
   * Returns the names of the tables.
   *
   * @return list<string>
   *   The names of the tables.
   */
  public function tableNames(): array {
    return array_map(
      fn(Table $table): string => $table->name,
      $this->tables,
    );
  }

  /**
   * Gets a specific table definition.
   *
   * @param string $name
   *   The name of the Table object to return.
   *
   * @return Table
   *   The Table object.
   *
   * @throws \Drupal\Core\Database\Exception\SchemaDefinitionException
   *   When the requested table is missing.
   */
  public function getTableDefinition(string $name): Table {
    $ret = array_find(
      $this->tables,
      function (Table $table) use ($name): bool {
        return $table->name === $name;
      },
    );
    if (!$ret) {
      throw new SchemaDefinitionException("Schema {$this->name} does not define a table named {$name}");
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    $spec = [];
    foreach ($this->tables as $table) {
      $spec[$table->name] = $table->toArray();
    }
    return $spec;
  }

}
