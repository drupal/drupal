<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\FetchModeTrait;
use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\Statement\ResultBase;

/**
 * Class for mysqli-provided results of a data query language (DQL) statement.
 */
class Result extends ResultBase {

  use FetchModeTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs $fetchMode
   *   The fetch mode.
   * @param array{class: class-string, constructor_args: list<mixed>, column: int, cursor_orientation?: int, cursor_offset?: int} $fetchOptions
   *   The fetch options.
   * @param \mysqli_result|false $mysqliResult
   *   The MySQLi result object.
   * @param \mysqli $mysqliConnection
   *   Client database connection object.
   */
  public function __construct(
    FetchAs $fetchMode,
    array $fetchOptions,
    protected readonly \mysqli_result|false $mysqliResult,
    protected readonly \mysqli $mysqliConnection,
  ) {
    parent::__construct($fetchMode, $fetchOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount(): ?int {
    // The most accurate value to return for Drupal here is the first
    // occurrence of an integer in the string stored by the connection's
    // $info property.
    // This is something like 'Rows matched: 1  Changed: 1  Warnings: 0' for
    // UPDATE or DELETE operations, 'Records: 2  Duplicates: 1  Warnings: 0'
    // for INSERT ones.
    // This however requires a regex parsing of the string which is expensive;
    // $affected_rows would be less accurate but much faster. We would need
    // Drupal to be less strict in testing, and never rely on this value in
    // runtime (which would be healthy anyway).
    if ($this->mysqliConnection->info !== NULL) {
      $matches = [];
      if (preg_match('/\s(\d+)\s/', $this->mysqliConnection->info, $matches) === 1) {
        return (int) $matches[0];
      }
      else {
        throw new DatabaseExceptionWrapper('Invalid data in the $info property of the mysqli connection - ' . $this->mysqliConnection->info);
      }
    }
    elseif ($this->mysqliConnection->affected_rows !== NULL) {
      return $this->mysqliConnection->affected_rows;
    }
    throw new DatabaseExceptionWrapper('Unable to retrieve affected rows data');
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode(FetchAs $mode, array $fetchOptions): bool {
    // There are no methods to set fetch mode in \mysqli_result.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FetchAs $mode, array $fetchOptions): array|object|int|float|string|bool|NULL {
    assert($this->mysqliResult instanceof \mysqli_result);

    $mysqli_row = $this->mysqliResult->fetch_assoc();

    if (!$mysqli_row) {
      return FALSE;
    }

    // Stringify all non-NULL column values.
    $row = array_map(fn ($value) => $value === NULL ? NULL : (string) $value, $mysqli_row);

    return $this->assocToFetchMode($row, $mode, $fetchOptions);
  }

}
