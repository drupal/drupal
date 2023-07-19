<?php

namespace Drupal\Core\Database;

/**
 * Provide helper methods for statement fetching.
 */
trait FetchModeTrait {

  /**
   * Converts a row of data in FETCH_ASSOC format to FETCH_BOTH.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   *
   * @return array
   *   The row in FETCH_BOTH format.
   */
  protected function assocToBoth(array $rowAssoc): array {
    // \PDO::FETCH_BOTH returns an array indexed by both the column name
    // and the column number.
    return $rowAssoc + array_values($rowAssoc);
  }

  /**
   * Converts a row of data in FETCH_ASSOC format to FETCH_NUM.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   *
   * @return array
   *   The row in FETCH_NUM format.
   */
  protected function assocToNum(array $rowAssoc): array {
    return array_values($rowAssoc);
  }

  /**
   * Converts a row of data in FETCH_ASSOC format to FETCH_OBJ.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   *
   * @return object
   *   The row in FETCH_OBJ format.
   */
  protected function assocToObj(array $rowAssoc): \stdClass {
    return (object) $rowAssoc;
  }

  /**
   * Converts a row of data in FETCH_ASSOC format to FETCH_CLASS.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   * @param string $className
   *   Name of the created class.
   * @param array $constructorArguments
   *   Elements of this array are passed to the constructor.
   *
   * @return object
   *   The row in FETCH_CLASS format.
   */
  protected function assocToClass(array $rowAssoc, string $className, array $constructorArguments): object {
    $classObj = new $className(...$constructorArguments);
    foreach ($rowAssoc as $column => $value) {
      $classObj->$column = $value;
    }
    return $classObj;
  }

  /**
   * Converts a row of data to FETCH_CLASS | FETCH_CLASSTYPE.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   * @param array $constructorArguments
   *   Elements of this array are passed to the constructor.
   *
   * @return object
   *   The row in FETCH_CLASS format.
   */
  protected function assocToClassType(array $rowAssoc, array $constructorArguments): object {
    $className = array_shift($rowAssoc);
    return $this->assocToClass($rowAssoc, $className, $constructorArguments);
  }

  /**
   * Fills an object with data from a FETCH_ASSOC row.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   * @param object $object
   *   The object receiving the data.
   *
   * @return object
   *   The object receiving the data.
   */
  protected function assocIntoObject(array $rowAssoc, object $object): object {
    foreach ($rowAssoc as $column => $value) {
      $object->$column = $value;
    }
    return $object;
  }

  /**
   * Converts a row of data in FETCH_ASSOC format to FETCH_COLUMN.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   * @param string[] $columnNames
   *   The list of the row columns.
   * @param int $columnIndex
   *   The index of the column to fetch the value of.
   *
   * @return string|false
   *   The value of the column, or FALSE if the column is not defined.
   */
  protected function assocToColumn(array $rowAssoc, array $columnNames, int $columnIndex): mixed {
    return $rowAssoc[$columnNames[$columnIndex]] ?? FALSE;
  }

}
