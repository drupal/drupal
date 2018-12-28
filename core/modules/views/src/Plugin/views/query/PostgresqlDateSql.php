<?php

namespace Drupal\views\Plugin\views\query;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * PostgreSQL-specific date handling.
 *
 * @internal
 *   This class should only be used by the Views SQL query plugin.
 *
 * @see \Drupal\views\Plugin\views\query\Sql
 */
class PostgresqlDateSql implements DateSqlInterface {

  use DependencySerializationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An array of PHP-to-PostgreSQL replacement patterns.
   *
   * @var array
   */
  protected static $replace = [
    'Y' => 'YYYY',
    'y' => 'YY',
    'M' => 'Mon',
    'm' => 'MM',
    // No format for Numeric representation of a month, without leading zeros.
    'n' => 'MM',
    'F' => 'Month',
    'D' => 'Dy',
    'd' => 'DD',
    'l' => 'Day',
    // No format for Day of the month without leading zeros.
    'j' => 'DD',
    'W' => 'IW',
    'H' => 'HH24',
    'h' => 'HH12',
    'i' => 'MI',
    's' => 'SS',
    'A' => 'AM',
  ];

  /**
   * Constructs the PostgreSQL-specific date sql class.
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
      // Ensures compatibility with field offset operation below.
      return "TO_TIMESTAMP($field, 'YYYY-MM-DD HH24:MI:SS')";
    }
    return "TO_TIMESTAMP($field)";
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat($field, $format) {
    $format = strtr($format, static::$replace);
    return "TO_CHAR($field, '$format')";
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldTimezoneOffset(&$field, $offset) {
    $field = "($field + INTERVAL '$offset SECONDS')";
  }

  /**
   * {@inheritdoc}
   */
  public function setTimezoneOffset($offset) {
    $this->database->query("SET TIME ZONE INTERVAL '$offset' HOUR TO MINUTE");
  }

}
