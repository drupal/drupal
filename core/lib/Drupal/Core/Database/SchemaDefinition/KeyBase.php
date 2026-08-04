<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

use Drupal\Core\Database\Exception\SchemaDefinitionException;

/**
 * Base class for table keys (primary, unique, index).
 */
abstract class KeyBase implements SchemaDefinitionInterface {

  /**
   * The key columns.
   *
   * @var list<KeyColumn>
   *   The list of KeyColumn objects.
   */
  public readonly array $columns;

  /**
   * Constructor.
   *
   * @param list<KeyColumn|string|array{0:string, 1:int}> $columns
   *   A mix of key column specifiers, being KeyColumn objects, strings naming
   *   columns, or arrays of two elements, column name and length, specifying a
   *   prefix of the named column.
   * @param bool $limitedLengthAllowed
   *   Defines if limited length columns are allowed.
   */
  public function __construct(
    array $columns,
    bool $limitedLengthAllowed,
  ) {
    $this->columns = $this->buildKeyColumns($columns, $limitedLengthAllowed);
  }

  /**
   * Builds an array of KeyColumn objects from a mixed list of columns.
   *
   * @param list<KeyColumn|string|array{0:string, 1:int}> $rawColumns
   *   A mix of key column specifiers, being KeyColumn objects, strings naming
   *   columns, or arrays of two elements, column name and length, specifying a
   *   prefix of the named column.
   * @param bool $limitedLengthAllowed
   *   Defines if limited length columns are allowed.
   *
   * @return list<KeyColumn>
   *   The normalized list of KeyColumn objects.
   */
  protected function buildKeyColumns(array $rawColumns, bool $limitedLengthAllowed): array {
    $columns = [];
    foreach ($rawColumns as $rawColumn) {
      if ($rawColumn instanceof KeyColumn) {
        if (!$limitedLengthAllowed && $rawColumn->length !== NULL) {
          throw new SchemaDefinitionException('Limited length columns not allowed in this context');
        }
        $columns[] = $rawColumn;
      }
      elseif (is_array($rawColumn)) {
        if (!$limitedLengthAllowed) {
          throw new SchemaDefinitionException('Limited length columns not allowed in this context');
        }
        $columns[] = new KeyColumn($rawColumn[0], $rawColumn[1]);
      }
      elseif (is_string($rawColumn)) {
        $columns[] = new KeyColumn($rawColumn);
      }
      else {
        throw new SchemaDefinitionException("Key columns should be defined as an array of KeyColumn objects, or strings indicating the column name, or arrays with column name and limited length");
      }
    }
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    $spec = [];
    foreach ($this->columns as $keyColumn) {
      $spec[] = $keyColumn->length !== NULL ? [$keyColumn->name, $keyColumn->length] : $keyColumn->name;
    }
    return $spec;
  }

}
