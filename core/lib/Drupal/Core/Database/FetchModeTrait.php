<?php

namespace Drupal\Core\Database;

/**
 * Provide helper methods for statement fetching.
 */
trait FetchModeTrait {

  /**
   * Map FETCH_* modes to their literal for inclusion in messages.
   *
   * @see https://github.com/php/php-src/blob/master/ext/pdo/php_pdo_driver.h#L65-L80
   */
  protected array $fetchModeLiterals = [
    \PDO::FETCH_DEFAULT => 'FETCH_DEFAULT',
    \PDO::FETCH_LAZY => 'FETCH_LAZY',
    \PDO::FETCH_ASSOC => 'FETCH_ASSOC',
    \PDO::FETCH_NUM => 'FETCH_NUM',
    \PDO::FETCH_BOTH => 'FETCH_BOTH',
    \PDO::FETCH_OBJ => 'FETCH_OBJ',
    \PDO::FETCH_BOUND => 'FETCH_BOUND',
    \PDO::FETCH_COLUMN => 'FETCH_COLUMN',
    \PDO::FETCH_CLASS => 'FETCH_CLASS',
    \PDO::FETCH_INTO => 'FETCH_INTO',
    \PDO::FETCH_FUNC => 'FETCH_FUNC',
    \PDO::FETCH_NAMED => 'FETCH_NAMED',
    \PDO::FETCH_KEY_PAIR => 'FETCH_KEY_PAIR',
    \PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE => 'FETCH_CLASS | FETCH_CLASSTYPE',
    \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE => 'FETCH_CLASS | FETCH_PROPS_LATE',
  ];

  /**
   * The fetch modes supported.
   */
  protected array $supportedFetchModes = [
    \PDO::FETCH_ASSOC,
    \PDO::FETCH_CLASS,
    \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
    \PDO::FETCH_COLUMN,
    \PDO::FETCH_NUM,
    \PDO::FETCH_OBJ,
  ];

  /**
   * Converts a row of data in FETCH_ASSOC format to FETCH_BOTH.
   *
   * @param array $rowAssoc
   *   A row of data in FETCH_ASSOC format.
   *
   * @return array
   *   The row in FETCH_BOTH format.
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use
   *   supported modes only.
   *
   * @see https://www.drupal.org/node/3377999
   */
  protected function assocToBoth(array $rowAssoc): array {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use supported modes only. See https://www.drupal.org/node/3377999', E_USER_DEPRECATED);
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
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use
   *   supported modes only.
   *
   * @see https://www.drupal.org/node/3377999
   */
  protected function assocToClassType(array $rowAssoc, array $constructorArguments): object {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use supported modes only. See https://www.drupal.org/node/3377999', E_USER_DEPRECATED);
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
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use
   *   supported modes only.
   *
   * @see https://www.drupal.org/node/3377999
   */
  protected function assocIntoObject(array $rowAssoc, object $object): object {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use supported modes only. See https://www.drupal.org/node/3377999', E_USER_DEPRECATED);
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
