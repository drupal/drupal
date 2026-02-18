<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Statement\FetchAs;

/**
 * Provide helper methods for statement fetching.
 */
trait FetchModeTrait {

  /**
   * Converts a row of data in associative format to list.
   *
   * @param array $rowAssoc
   *   A row of data in associative format.
   *
   * @return array
   *   The row in list format.
   */
  protected function assocToNum(array $rowAssoc): array {
    return array_values($rowAssoc);
  }

  /**
   * Converts a row of data in associative format to object.
   *
   * @param array $rowAssoc
   *   A row of data in associative format.
   *
   * @return object
   *   The row in object format.
   */
  protected function assocToObj(array $rowAssoc): \stdClass {
    return (object) $rowAssoc;
  }

  /**
   * Converts a row of data in associative format to classed object.
   *
   * @param array $rowAssoc
   *   A row of data in associative format.
   * @param string $className
   *   Name of the created class.
   * @param array $constructorArguments
   *   Elements of this array are passed to the constructor.
   *
   * @return object
   *   The row in classed object format.
   */
  protected function assocToClass(array $rowAssoc, string $className, array $constructorArguments): object {
    $classObj = new $className(...$constructorArguments);
    foreach ($rowAssoc as $column => $value) {
      $classObj->$column = $value;
    }
    return $classObj;
  }

  /**
   * Converts a row of data in associative format to column.
   *
   * @param array $rowAssoc
   *   A row of data in associative format.
   * @param string[] $columnNames
   *   The list of the row columns.
   * @param int $columnIndex
   *   The index of the column to fetch the value of.
   *
   * @return string
   *   The value of the column.
   *
   * @throws \ValueError
   *   If the column index is not defined.
   */
  protected function assocToColumn(array $rowAssoc, array $columnNames, int $columnIndex): mixed {
    if (!isset($columnNames[$columnIndex])) {
      throw new \ValueError('Invalid column index');
    }
    return $rowAssoc[$columnNames[$columnIndex]];
  }

  /**
   * Converts a row of data in associative format to a specified format.
   *
   * @param array $rowAssoc
   *   A row of data in FetchAs::Associative format.
   * @param \Drupal\Core\Database\Statement\FetchAs $mode
   *   The target target mode.
   * @param array $fetchOptions
   *   The fetch mode options.
   *
   * @return array<scalar|null>|object|scalar|null|false
   *   The data in the target mode.
   *
   * @throws \ValueError
   *   If the column index is not defined.
   */
  protected function assocToFetchMode(array $rowAssoc, FetchAs $mode, array $fetchOptions): array|object|int|float|string|bool|NULL {
    return match($mode) {
      FetchAs::Associative => $rowAssoc,
      FetchAs::ClassObject => $this->assocToClass($rowAssoc, $fetchOptions['class'], $fetchOptions['constructor_args']),
      FetchAs::Column => $this->assocToColumn($rowAssoc, array_keys($rowAssoc), $fetchOptions['column']),
      FetchAs::List => $this->assocToNum($rowAssoc),
      FetchAs::Object => $this->assocToObj($rowAssoc),
    };
  }

}
