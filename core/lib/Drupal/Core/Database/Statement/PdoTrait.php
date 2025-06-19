<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Statement;

/**
 * A trait for calling \PDOStatement methods.
 */
trait PdoTrait {

  /**
   * Converts a FetchAs mode to a \PDO::FETCH_* constant value.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs $mode
   *   The FetchAs mode.
   *
   * @return int
   *   A \PDO::FETCH_* constant value.
   */
  protected function fetchAsToPdo(FetchAs $mode): int {
    return match ($mode) {
      FetchAs::Associative => \PDO::FETCH_ASSOC,
      FetchAs::ClassObject => \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
      FetchAs::Column => \PDO::FETCH_COLUMN,
      FetchAs::List => \PDO::FETCH_NUM,
      FetchAs::Object => \PDO::FETCH_OBJ,
    };
  }

  /**
   * Converts a \PDO::FETCH_* constant value to a FetchAs mode.
   *
   * @param int $mode
   *   The \PDO::FETCH_* constant value.
   *
   * @return \Drupal\Core\Database\Statement\FetchAs
   *   A FetchAs mode.
   */
  protected function pdoToFetchAs(int $mode): FetchAs {
    return match ($mode) {
      \PDO::FETCH_ASSOC => FetchAs::Associative,
      \PDO::FETCH_CLASS, \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE => FetchAs::ClassObject,
      \PDO::FETCH_COLUMN => FetchAs::Column,
      \PDO::FETCH_NUM => FetchAs::List,
      \PDO::FETCH_OBJ => FetchAs::Object,
      default => throw new \RuntimeException('Fetch mode ' . ($this->fetchModeLiterals[$mode] ?? $mode) . ' is not supported. Use supported modes only.'),
    };
  }

  /**
   * Returns the client-level database statement object.
   *
   * This method should normally be used only within database driver code.
   *
   * @return object
   *   The client-level database statement.
   *
   * @throws \RuntimeException
   *   If the client-level statement is not set.
   */
  abstract public function getClientStatement(): object;

  /**
   * Sets the default fetch mode for the PDO statement.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs $mode
   *   One of the cases of the FetchAs enum.
   * @param int|class-string|null $columnOrClass
   *   If $mode is FetchAs::Column, the index of the column to fetch.
   *   If $mode is FetchAs::ClassObject, the FQCN of the object.
   * @param list<mixed>|null $constructorArguments
   *   If $mode is FetchAs::ClassObject, the arguments to pass to the
   *   constructor.
   *
   * @return bool
   *   Returns true on success or false on failure.
   */
  protected function clientSetFetchMode(FetchAs $mode, int|string|null $columnOrClass = NULL, array|null $constructorArguments = NULL): bool {
    return match ($mode) {
      FetchAs::Column => $this->getClientStatement()->setFetchMode(
        \PDO::FETCH_COLUMN,
        $columnOrClass ?? $this->fetchOptions['column'],
      ),
      FetchAs::ClassObject => $this->getClientStatement()->setFetchMode(
        \PDO::FETCH_CLASS,
        $columnOrClass ?? $this->fetchOptions['class'],
        $constructorArguments ?? $this->fetchOptions['constructor_args'],
      ),
      default => $this->getClientStatement()->setFetchMode(
        $this->fetchAsToPdo($mode),
      ),
    };
  }

  /**
   * Executes the prepared PDO statement.
   *
   * @param array|null $arguments
   *   An array of values with as many elements as there are bound parameters in
   *   the SQL statement being executed. This can be NULL.
   * @param array $options
   *   An array of options for this query.
   *
   * @return bool
   *   TRUE on success, or FALSE on failure.
   */
  protected function clientExecute(?array $arguments = [], array $options = []): bool {
    return $this->getClientStatement()->execute($arguments);
  }

  /**
   * Fetches the next row from the PDO statement.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs|null $mode
   *   (Optional) one of the cases of the FetchAs enum. If not specified,
   *   defaults to what is specified by setFetchMode().
   * @param int|null $cursorOrientation
   *   Not implemented in all database drivers, don't use.
   * @param int|null $cursorOffset
   *   Not implemented in all database drivers, don't use.
   *
   * @return array<scalar|null>|object|scalar|null|false
   *   A result, formatted according to $mode, or FALSE on failure.
   */
  protected function clientFetch(?FetchAs $mode = NULL, ?int $cursorOrientation = NULL, ?int $cursorOffset = NULL): array|object|int|float|string|bool|NULL {
    return match(func_num_args()) {
      0 => $this->getClientStatement()->fetch(),
      1 => $this->getClientStatement()->fetch($this->fetchAsToPdo($mode)),
      2 => $this->getClientStatement()->fetch($this->fetchAsToPdo($mode), $cursorOrientation),
      default => $this->getClientStatement()->fetch($this->fetchAsToPdo($mode), $cursorOrientation, $cursorOffset),
    };
  }

  /**
   * Returns a single column from the next row of a result set.
   *
   * @param int $column
   *   0-indexed number of the column to retrieve from the row. If no value is
   *   supplied, the first column is fetched.
   *
   * @return scalar|null|false
   *   A single column from the next row of a result set or false if there are
   *   no more rows.
   */
  protected function clientFetchColumn(int $column = 0): int|float|string|bool|NULL {
    return $this->getClientStatement()->fetchColumn($column);
  }

  /**
   * Fetches the next row and returns it as an object.
   *
   * @param class-string|null $class
   *   FQCN of the class to be instantiated.
   * @param list<mixed>|null $constructorArguments
   *   The arguments to be passed to the constructor.
   *
   * @return object|false
   *   An instance of the required class with property names that correspond
   *   to the column names, or FALSE on failure.
   */
  protected function clientFetchObject(?string $class = NULL, array $constructorArguments = []): object|FALSE {
    if ($class) {
      return $this->getClientStatement()->fetchObject($class, $constructorArguments);
    }
    return $this->getClientStatement()->fetchObject();
  }

  /**
   * Returns an array containing all of the result set rows.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs|null $mode
   *   (Optional) one of the cases of the FetchAs enum. If not specified,
   *   defaults to what is specified by setFetchMode().
   * @param int|class-string|null $columnOrClass
   *   If $mode is FetchAs::Column, the index of the column to fetch.
   *   If $mode is FetchAs::ClassObject, the FQCN of the object.
   * @param list<mixed>|null $constructorArguments
   *   If $mode is FetchAs::ClassObject, the arguments to pass to the
   *   constructor.
   *
   * @return array<array<scalar|null>|object|scalar|null>
   *   An array of results.
   */
  protected function clientFetchAll(?FetchAs $mode = NULL, int|string|null $columnOrClass = NULL, array|null $constructorArguments = NULL): array {
    return match ($mode) {
      FetchAs::Column => $this->getClientStatement()->fetchAll(
        \PDO::FETCH_COLUMN,
        $columnOrClass ?? $this->fetchOptions['column'],
      ),
      FetchAs::ClassObject => $this->getClientStatement()->fetchAll(
        \PDO::FETCH_CLASS,
        $columnOrClass ?? $this->fetchOptions['class'],
        $constructorArguments ?? $this->fetchOptions['constructor_args'],
      ),
      default => $this->getClientStatement()->fetchAll(
        $this->fetchAsToPdo($mode ?? $this->fetchMode),
      ),
    };
  }

  /**
   * Returns the number of rows affected by the last SQL statement.
   *
   * @return int
   *   The number of rows.
   */
  protected function clientRowCount(): int {
    return $this->getClientStatement()->rowCount();
  }

  /**
   * Returns the query string used to prepare the statement.
   *
   * @return string
   *   The query string.
   */
  protected function clientQueryString(): string {
    return $this->getClientStatement()->queryString;
  }

}
