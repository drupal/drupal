<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Database\Exception\SchemaDefinitionException;

/**
 * Describes a database table.
 */
final class Table implements SchemaDefinitionInterface {

  /**
   * Constructor.
   *
   * @param string $name
   *   The table name.
   * @param ?Column[] $columns
   *   (Optional) An array that describes the table's database columns. This is
   *   normally mandatory. Some methods in the Schema API - for example
   *   Schema::addField() - require partial table definition, so this is
   *   optional to support it.
   * @param ?string $description
   *   (Optional) A string in non-markup plain text describing this table and
   *   its purpose. References to other tables should be enclosed in curly
   *   brackets.
   * @param ?PrimaryKey $primaryKey
   *   (Optional) The primary key of the table.
   * @param ?UniqueKey[] $uniqueKeys
   *   (Optional) An array of unique keys for the table.
   * @param ?Index[] $indexes
   *   (Optional) An array of indexes for the table.
   * @param ?ForeignKey[] $foreignKeys
   *   (Optional) An array of foreign keys for the table. This argument is for
   *   documentation purposes only; foreign keys are not created in the
   *   database, nor are they enforced by Drupal.
   * @param array<string,array<string,mixed>>|null $dbSpecificExtra
   *   (Optional) If you need to define a table requiring information not
   *   included elsewhere, you can specify it for each database backend.
   *   Specify this as an associative array having the database type ('mysql',
   *   'sqlite', 'pgsql', 'oracle', etc.) as the key, and an array of extra
   *   information <string,mixed> as the value.
   */
  public function __construct(
    public readonly string $name,
    public readonly ?array $columns = NULL,
    public readonly ?string $description = NULL,
    public readonly ?PrimaryKey $primaryKey = NULL,
    public readonly ?array $uniqueKeys = NULL,
    public readonly ?array $indexes = NULL,
    public readonly ?array $foreignKeys = NULL,
    public readonly ?array $dbSpecificExtra = NULL,
  ) {
    $this->validate();
  }

  /**
   * Validates the properties of the value object.
   *
   * @throws \Drupal\Core\Database\Exception\SchemaDefinitionException
   */
  private function validate(): void {

    if ($this->columns && !Inspector::assertAllObjects($this->columns, Column::class)) {
      throw new SchemaDefinitionException("All members of the 'columns' argument of the '{$this->name}' Table must be Column objects");
    }
    if ($this->uniqueKeys && !Inspector::assertAllObjects($this->uniqueKeys, UniqueKey::class)) {
      throw new SchemaDefinitionException("All members of the 'uniqueKeys' argument of the '{$this->name}' Table must be UniqueKey objects");
    }
    if ($this->indexes && !Inspector::assertAllObjects($this->indexes, Index::class)) {
      throw new SchemaDefinitionException("All members of the 'indexes' argument of the '{$this->name}' Table must be Index objects");
    }
    if ($this->foreignKeys && !Inspector::assertAllObjects($this->foreignKeys, ForeignKey::class)) {
      throw new SchemaDefinitionException("All members of the 'foreignKeys' argument of the '{$this->name}' Table must be ForeignKey objects");
    }

  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    $spec = [];
    if ($this->description !== NULL) {
      $spec['description'] = $this->description;
    }
    if ($this->columns !== NULL) {
      foreach ($this->columns as $column) {
        $spec['fields'][$column->name] = $column->toArray();
      }
    }
    if ($this->primaryKey !== NULL) {
      $spec['primary key'] = $this->primaryKey->toArray();
    }
    if ($this->uniqueKeys !== NULL) {
      foreach ($this->uniqueKeys as $uniqueKey) {
        $spec['unique keys'][$uniqueKey->name] = $uniqueKey->toArray();
      }
    }
    if ($this->indexes !== NULL) {
      foreach ($this->indexes as $index) {
        $spec['indexes'][$index->name] = $index->toArray();
      }
    }
    if ($this->foreignKeys !== NULL) {
      foreach ($this->foreignKeys as $foreignKey) {
        $spec['foreign keys'][$foreignKey->name] = $foreignKey->toArray();
      }
    }
    if ($this->dbSpecificExtra !== NULL) {
      foreach ($this->dbSpecificExtra as $extra) {
        foreach ($extra as $key => $value) {
          $spec[$key] = $value;
        }
      }
    }
    return $spec;
  }

}
