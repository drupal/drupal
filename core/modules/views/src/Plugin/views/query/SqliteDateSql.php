<?php

namespace Drupal\views\Plugin\views\query;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * SQLite-specific date handling.
 *
 * @internal
 *   This class should only be used by the Views SQL query plugin.
 *
 * @see \Drupal\views\Plugin\views\query\Sql
 */
class SqliteDateSql implements DateSqlInterface {

  use DependencySerializationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An array of PHP-to-SQLite date replacement patterns.
   *
   * @var array
   */
  protected static $replace = [
    'Y' => '%Y',
    // No format for 2 digit year number.
    'y' => '%Y',
    // No format for 3 letter month name.
    'M' => '%m',
    'm' => '%m',
    // No format for month number without leading zeros.
    'n' => '%m',
    // No format for full month name.
    'F' => '%m',
    // No format for 3 letter day name.
    'D' => '%d',
    'd' => '%d',
    // No format for full day name.
    'l' => '%d',
    // no format for day of month number without leading zeros.
    'j' => '%d',
    'W' => '%W',
    'H' => '%H',
    // No format for 12 hour hour with leading zeros.
    'h' => '%H',
    'i' => '%M',
    's' => '%S',
    // No format for AM/PM.
    'A' => '',
  ];

  /**
   * Constructs the SQLite-specific date sql class.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateField($field, $string_date) {
    if ($string_date) {
      $field = "strftime('%s', $field)";
    }
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat($field, $format) {
    $format = strtr($format, static::$replace);

    // SQLite does not have a ISO week substitution string, so it needs special
    // handling.
    // @see http://wikipedia.org/wiki/ISO_week_date#Calculation
    // @see http://stackoverflow.com/a/15511864/1499564
    if ($format === '%W') {
      $expression = "((strftime('%j', date(strftime('%Y-%m-%d', $field, 'unixepoch'), '-3 days', 'weekday 4')) - 1) / 7 + 1)";
    }
    else {
      $expression = "strftime('$format', $field, 'unixepoch')";
    }
    // The expression yields a string, but the comparison value is an integer in
    // case the comparison value is a float, integer, or numeric. All of the
    // above SQLite format tokens only produce integers. However, the given
    // $format may contain 'Y-m-d', which results in a string.
    // @see \Drupal\Core\Database\Driver\sqlite\Connection::expandArguments()
    // @see http://www.sqlite.org/lang_datefunc.html
    // @see http://www.sqlite.org/lang_expr.html#castexpr
    if (preg_match('/^(?:%\w)+$/', $format)) {
      $expression = "CAST($expression AS NUMERIC)";
    }
    return $expression;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimezoneOffset($offset) {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldTimezoneOffset(&$field, $offset, $string_date = FALSE) {
    if (!empty($offset)) {
      $field = "($field + $offset)";
    }
  }

}
