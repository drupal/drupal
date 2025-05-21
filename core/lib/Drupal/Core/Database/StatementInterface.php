<?php

namespace Drupal\Core\Database;

/**
 * Represents a prepared statement.
 *
 * Child implementations should either extend StatementWrapperIterator:
 * @code
 * class Drupal\my_module\Driver\Database\my_driver\Statement extends Drupal\Core\Database\StatementWrapperIterator {}
 * @endcode
 * or define their own class. If defining their own class, they will also have
 * to implement either the \Iterator or \IteratorAggregate interface before
 * Drupal\Core\Database\StatementInterface:
 * @code
 * class Drupal\my_module\Driver\Database\my_driver\Statement implements Iterator, Drupal\Core\Database\StatementInterface {}
 * @endcode
 *
 * @ingroup database
 */
interface StatementInterface extends \Traversable {

  /**
   * Executes a prepared statement.
   *
   * @param array|null $args
   *   An array of values with as many elements as there are bound parameters in
   *   the SQL statement being executed. This can be NULL.
   * @param array $options
   *   An array of options for this query.
   *
   * @return bool
   *   TRUE on success, or FALSE on failure.
   */
  public function execute($args = [], $options = []);

  /**
   * Gets the query string of this statement.
   *
   * @return string
   *   The query string, in its form with placeholders.
   */
  public function getQueryString();

  /**
   * Returns the target connection this statement is associated with.
   *
   * @return string
   *   The target connection string of this statement.
   */
  public function getConnectionTarget(): string;

  /**
   * Returns the number of rows matched by the last SQL statement.
   *
   * @return int
   *   The number of rows matched by the last DELETE, INSERT, or UPDATE
   *   statement executed or throws \Drupal\Core\Database\RowCountException
   *   if the last executed statement was SELECT.
   *
   * @throws \Drupal\Core\Database\RowCountException
   */
  public function rowCount();

  /**
   * Sets the default fetch mode for this statement.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs|int $mode
   *   One of the cases of the FetchAs enum, or (deprecated) a \PDO::FETCH_*
   *   constant.
   * @param string|int|null $a1
   *   An option depending of the fetch mode specified by $mode:
   *   - for FetchAs::Column, the index of the column to fetch;
   *   - for FetchAs::ClassObject, the name of the class to create.
   * @param list<mixed> $a2
   *   If $mode is FetchAs::ClassObject, the optional arguments to pass to the
   *   - for \PDO::FETCH_COLUMN, the index of the column to fetch.
   *   - for \PDO::FETCH_CLASS, the name of the class to create.
   *   - for \PDO::FETCH_INTO, the object to add the data to.
   *   constructor.
   *
   * @return bool
   *   TRUE if successful, FALSE if not.
   */
  public function setFetchMode($mode, $a1 = NULL, $a2 = []);

  /**
   * Fetches the next row from a result set.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs|int|null $mode
   *   (Optional) one of the cases of the FetchAs enum, or (deprecated) a
   *   \PDO::FETCH_* constant. If not specified, defaults to what is specified
   *   by setFetchMode().
   * @param int|null $cursor_orientation
   *   Not implemented in all database drivers, don't use.
   * @param int|null $cursor_offset
   *   Not implemented in all database drivers, don't use.
   *
   * @return array|object|false
   *   A result, formatted according to $mode, or FALSE on failure.
   */
  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL);

  /**
   * Returns a single field from the next record of a result set.
   *
   * @param int $index
   *   The numeric index of the field to return. Defaults to the first field.
   *
   * @return mixed
   *   A single field from the next record, or FALSE if there is no next record.
   *
   * @throws \ValueError
   *   If there is a record and the column index is not defined.
   */
  public function fetchField($index = 0);

  /**
   * Fetches the next row and returns it as an object.
   *
   * The object will be of the class specified by
   * StatementInterface::setFetchMode() or stdClass if not specified.
   *
   * @param string|null $class_name
   *   Name of the created class.
   * @param array $constructor_arguments
   *   Elements of this array are passed to the constructor.
   *
   * @return mixed
   *   The object of specified class or \stdClass if not specified. Returns
   *   FALSE or NULL if there is no next row.
   */
  public function fetchObject(?string $class_name = NULL, array $constructor_arguments = []);

  /**
   * Fetches the next row and returns it as an associative array.
   *
   * This method corresponds to \PDOStatement::fetchObject(), but for
   * associative arrays. For some reason \PDOStatement does not have a
   * corresponding array helper method, so one is added.
   *
   * @return array|bool
   *   An associative array, or FALSE if there is no next row.
   */
  public function fetchAssoc();

  /**
   * Returns an array containing all of the result set rows.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs|int|null $mode
   *   (Optional) one of the cases of the FetchAs enum, or (deprecated) a
   *   \PDO::FETCH_* constant. If not specified, defaults to what is specified
   *   by setFetchMode().
   * @param int|null $column_index
   *   If $mode is FetchAs::Column, the index of the column to fetch.
   * @param array $constructor_arguments
   *   If $mode is FetchAs::ClassObject, the arguments to pass to the
   *   constructor.
   *
   * @return array
   *   An array of results.
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $constructor_arguments = NULL);

  /**
   * Returns an entire single column of a result set as an indexed array.
   *
   * Note that this method will run the result set to the end.
   *
   * @param int $index
   *   The index of the column number to fetch.
   *
   * @return array
   *   An indexed array, or an empty array if there is no result set.
   *
   * @throws \ValueError
   *   If there is at least one record but the column index is not defined.
   */
  public function fetchCol($index = 0);

  /**
   * Returns the entire result set as a single associative array.
   *
   * This method is only useful for two-column result sets. It will return an
   * associative array where the key is one column from the result set and the
   * value is another field. In most cases, the default of the first two columns
   * is appropriate.
   *
   * Note that this method will run the result set to the end.
   *
   * @param int $key_index
   *   The numeric index of the field to use as the array key.
   * @param int $value_index
   *   The numeric index of the field to use as the array value.
   *
   * @return array
   *   An associative array, or an empty array if there is no result set.
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1);

  /**
   * Returns the result set as an associative array keyed by the given field.
   *
   * If the given key appears multiple times, later records will overwrite
   * earlier ones.
   *
   * @param string $key
   *   The name of the field on which to index the array.
   * @param \Drupal\Core\Database\Statement\FetchAs|int|string|null $fetch
   *   (Optional) the fetch mode to use. One of the cases of the FetchAs enum,
   *   or (deprecated) a \PDO::FETCH_* constant. If set to FetchAs::Associative
   *   or FetchAs::List the returned value with be an array of arrays. For any
   *   other value it will be an array of objects. If not specified, defaults to
   *   what is specified by setFetchMode().
   *
   * @return array
   *   An associative array, or an empty array if there is no result set.
   */
  public function fetchAllAssoc($key, $fetch = NULL);

}
