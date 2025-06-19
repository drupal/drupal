<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli;

use Drupal\Core\Database\Connection as BaseConnection;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseAccessDeniedException;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Transaction\TransactionManagerInterface;
use Drupal\mysql\Driver\Database\mysql\Connection as BaseMySqlConnection;

/**
 * MySQLi implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends BaseMySqlConnection {

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = Statement::class;

  public function __construct(
    \mysqli $connection,
    array $connectionOptions = [],
  ) {
    // If the SQL mode doesn't include 'ANSI_QUOTES' (explicitly or via a
    // combination mode), then MySQL doesn't interpret a double quote as an
    // identifier quote, in which case use the non-ANSI-standard backtick.
    //
    // @see https://dev.mysql.com/doc/refman/8.0/en/sql-mode.html#sqlmode_ansi_quotes
    $ansiQuotesModes = ['ANSI_QUOTES', 'ANSI'];
    $isAnsiQuotesMode = FALSE;
    if (isset($connectionOptions['init_commands']['sql_mode'])) {
      foreach ($ansiQuotesModes as $mode) {
        // None of the modes in $ansiQuotesModes are substrings of other modes
        // that are not in $ansiQuotesModes, so a simple stripos() does not
        // return false positives.
        if (stripos($connectionOptions['init_commands']['sql_mode'], $mode) !== FALSE) {
          $isAnsiQuotesMode = TRUE;
          break;
        }
      }
    }

    if ($this->identifierQuotes === ['"', '"'] && !$isAnsiQuotesMode) {
      $this->identifierQuotes = ['`', '`'];
    }

    BaseConnection::__construct($connection, $connectionOptions);
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []) {
    // Sets mysqli error reporting mode to report errors from mysqli function
    // calls and to throw mysqli_sql_exception for errors.
    // @see https://www.php.net/manual/en/mysqli-driver.report-mode.php
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Allow PDO options to be overridden.
    $connection_options += [
      'pdo' => [],
    ];

    try {
      $mysqli = @new \mysqli(
        $connection_options['host'],
        $connection_options['username'],
        $connection_options['password'],
        $connection_options['database'] ?? '',
        !empty($connection_options['port']) ? (int) $connection_options['port'] : 3306,
        $connection_options['unix_socket'] ?? ''
      );
      if (!$mysqli->set_charset('utf8mb4')) {
        throw new InvalidCharsetException('Invalid charset utf8mb4');
      }
    }
    catch (\mysqli_sql_exception $e) {
      if ($e->getCode() === static::DATABASE_NOT_FOUND) {
        throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);
      }
      elseif ($e->getCode() === static::ACCESS_DENIED) {
        throw new DatabaseAccessDeniedException($e->getMessage(), $e->getCode(), $e);
      }

      throw new ConnectionNotDefinedException('Invalid database connection: ' . $e->getMessage(), $e->getCode(), $e);
    }

    // Force MySQL to use the UTF-8 character set. Also set the collation, if a
    // certain one has been set; otherwise, MySQL defaults to
    // 'utf8mb4_0900_ai_ci' for the 'utf8mb4' character set.
    if (!empty($connection_options['collation'])) {
      $mysqli->query('SET NAMES utf8mb4 COLLATE ' . $connection_options['collation']);
    }
    else {
      $mysqli->query('SET NAMES utf8mb4');
    }

    // Set MySQL init_commands if not already defined.  Default Drupal's MySQL
    // behavior to conform more closely to SQL standards.  This allows Drupal
    // to run almost seamlessly on many different kinds of database systems.
    // These settings force MySQL to behave the same as postgresql, or sqlite
    // in regard to syntax interpretation and invalid data handling.  See
    // https://www.drupal.org/node/344575 for further discussion. Also, as MySQL
    // 5.5 changed the meaning of TRADITIONAL we need to spell out the modes one
    // by one.
    $connection_options += [
      'init_commands' => [],
    ];

    $connection_options['init_commands'] += [
      'sql_mode' => "SET sql_mode = 'ANSI,TRADITIONAL'",
    ];
    if (!empty($connection_options['isolation_level'])) {
      $connection_options['init_commands'] += [
        'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL ' . strtoupper($connection_options['isolation_level']),
      ];
    }

    // Execute initial commands.
    foreach ($connection_options['init_commands'] as $sql) {
      $mysqli->query($sql);
    }

    return $mysqli;
  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'mysqli';
  }

  /**
   * {@inheritdoc}
   */
  public function clientVersion() {
    return \mysqli_get_client_info();
  }

  /**
   * {@inheritdoc}
   */
  public function createDatabase($database): void {
    // Escape the database name.
    $database = Database::getConnection()->escapeDatabase($database);

    try {
      // Create the database and set it as active.
      $this->connection->query("CREATE DATABASE $database");
      $this->connection->query("USE $database");
    }
    catch (\Exception $e) {
      throw new DatabaseNotFoundException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function quote($string, $parameter_type = \PDO::PARAM_STR) {
    return "'" . $this->connection->escape_string((string) $string) . "'";
  }

  /**
   * {@inheritdoc}
   */
  public function lastInsertId(?string $name = NULL): string {
    return (string) $this->connection->insert_id;
  }

  /**
   * {@inheritdoc}
   */
  public function exceptionHandler() {
    return new ExceptionHandler();
  }

  /**
   * {@inheritdoc}
   */
  protected function driverTransactionManager(): TransactionManagerInterface {
    return new TransactionManager($this);
  }

}
