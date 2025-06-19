<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Statement;

/**
 * Class for PDO-provided results of a data query language (DQL) statement.
 */
class PdoResult extends ResultBase {

  use PdoTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs $fetchMode
   *   The fetch mode.
   * @param array{class: class-string, constructor_args: list<mixed>, column: int, cursor_orientation?: int, cursor_offset?: int} $fetchOptions
   *   The fetch options.
   * @param \PDOStatement $clientStatement
   *   The PDO Statement object. PDO does not provide a separate object for
   *   results, se we need to fetch data from the Statement.
   */
  public function __construct(
    FetchAs $fetchMode,
    array $fetchOptions,
    protected readonly \PDOStatement $clientStatement,
  ) {
    parent::__construct($fetchMode, $fetchOptions);
  }

  /**
   * Returns the client-level database PDO statement object.
   *
   * This method should normally be used only within database driver code.
   *
   * @return \PDOStatement
   *   The client-level database PDO statement.
   */
  public function getClientStatement(): \PDOStatement {
    return $this->clientStatement;
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount(): ?int {
    return $this->clientRowCount();
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode(FetchAs $mode, array $fetchOptions): bool {
    return match ($mode) {
      FetchAs::ClassObject => $this->clientSetFetchMode($mode, $fetchOptions['class'], $fetchOptions['constructor_args'] ?? NULL),
      FetchAs::Column => $this->clientSetFetchMode($mode, $fetchOptions['column']),
      default => $this->clientSetFetchMode($mode),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FetchAs $mode, array $fetchOptions): array|object|int|float|string|bool|NULL {
    if (!empty($fetchOptions)) {
      $this->setFetchMode($mode, $fetchOptions);
    }
    if (isset($fetchOptions['cursor_orientation'])) {
      if (isset($fetchOptions['cursor_offset'])) {
        return $this->clientFetch($mode, $fetchOptions['cursor_orientation'], $fetchOptions['cursor_offset']);
      }
      return $this->clientFetch($mode, $fetchOptions['cursor_orientation']);
    }
    return $this->clientFetch($mode);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll(FetchAs $mode, array $fetchOptions): array {
    return $this->clientFetchAll($mode, $fetchOptions['column'] ?? $fetchOptions['class'] ?? NULL, $fetchOptions['constructor_args'] ?? NULL);
  }

}
