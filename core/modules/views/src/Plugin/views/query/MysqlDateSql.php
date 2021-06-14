<?php

namespace Drupal\views\Plugin\views\query;

use Drupal\Core\Database\Connection;

/**
 * MySQL-specific date handling.
 *
 * @internal
 *   This class should only be used by the Views SQL query plugin.
 *
 * @see \Drupal\views\Plugin\views\query\Sql
 */
class MysqlDateSql implements DateSqlInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An array of PHP-to-MySQL replacement patterns.
   */
  protected static $replace = [
    'Y' => '%Y',
    'y' => '%y',
    'M' => '%b',
    'm' => '%m',
    'n' => '%c',
    'F' => '%M',
    'D' => '%a',
    'd' => '%d',
    'l' => '%W',
    'j' => '%e',
    'W' => '%v',
    'H' => '%H',
    'h' => '%h',
    'i' => '%i',
    's' => '%s',
    'A' => '%p',
  ];

  /**
   * Constructs the MySQL-specific date sql class.
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
      return $field;
    }

    // Base date field storage is timestamp, so the date to be returned here is
    // epoch + stored value (seconds from epoch).
    return "DATE_ADD('19700101', INTERVAL $field SECOND)";
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat($field, $format) {
    $format = strtr($format, static::$replace);
    return "DATE_FORMAT($field, '$format')";
  }

  /**
   * {@inheritdoc}
   */
  public function setTimezoneOffset($offset) {
    $this->database->query("SET @@session.time_zone = '$offset'");
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldTimezoneOffset(&$field, $offset) {
    if (!empty($offset)) {
      $field = "($field + INTERVAL $offset SECOND)";
    }
  }

}
