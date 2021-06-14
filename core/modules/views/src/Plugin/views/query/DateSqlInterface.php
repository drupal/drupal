<?php

namespace Drupal\views\Plugin\views\query;

/**
 * Defines an interface for handling date queries with SQL.
 *
 * @internal
 *   Classes implementing this interface should only be used by the Views SQL
 *   query plugin.
 *
 * @see \Drupal\views\Plugin\views\query\Sql
 */
interface DateSqlInterface {

  /**
   * Returns a native database expression for a given field.
   *
   * @param string $field
   *   The query field that will be used in the expression.
   * @param bool $string_date
   *   For certain databases, date format functions vary depending on string or
   *   numeric storage.
   *
   * @return string
   *   An expression representing a date field with timezone.
   */
  public function getDateField($field, $string_date);

  /**
   * Creates a native database date formatting.
   *
   * @param string $field
   *   An appropriate query expression pointing to the date field.
   * @param string $format
   *   A format string for the result. For example: 'Y-m-d H:i:s'.
   *
   * @return string
   *   A string representing the field formatted as a date as specified by
   *   $format.
   */
  public function getDateFormat($field, $format);

  /**
   * Applies the given offset to the given field.
   *
   * @param string &$field
   *   The date field in a string format.
   * @param int $offset
   *   The timezone offset in seconds.
   */
  public function setFieldTimezoneOffset(&$field, $offset);

  /**
   * Set the database to the given timezone.
   *
   * @param string $offset
   *   The timezone.
   */
  public function setTimezoneOffset($offset);

}
