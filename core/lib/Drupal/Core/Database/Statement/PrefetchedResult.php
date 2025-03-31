<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Statement;

use Drupal\Core\Database\FetchModeTrait;

/**
 * Class for prefetched results of a data query language (DQL) statement.
 */
class PrefetchedResult extends ResultBase {

  use FetchModeTrait;

  /**
   * The column names.
   */
  public readonly array $columnNames;

  /**
   * The current row index in the result set.
   */
  protected ?int $currentRowIndex = NULL;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Statement\FetchAs $fetchMode
   *   The fetch mode.
   * @param array{class: class-string, constructor_args: list<mixed>, column: int, cursor_orientation?: int, cursor_offset?: int} $fetchOptions
   *   The fetch options.
   * @param array $data
   *   The prefetched data, in FetchAs::Associative format.
   * @param int|null $rowCount
   *   The row count.
   */
  public function __construct(
    FetchAs $fetchMode,
    array $fetchOptions,
    protected array $data,
    public readonly ?int $rowCount,
  ) {
    parent::__construct($fetchMode, $fetchOptions);
    $this->columnNames = isset($this->data[0]) ? array_keys($this->data[0]) : [];
    $this->currentRowIndex = -1;
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount(): ?int {
    return $this->rowCount;
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode(FetchAs $mode, array $fetchOptions): bool {
    // We do not really need to do anything here, since calls to any of this
    // class' methods require an explicit fetch mode to be passed in, and we
    // have no longer an active client statement to which we may want to pass
    // the default fetch mode. Just return TRUE.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FetchAs $mode, array $fetchOptions): array|object|int|float|string|bool|NULL {
    $this->currentRowIndex++;
    if (!isset($this->data[$this->currentRowIndex])) {
      $this->currentRowIndex = NULL;
      return FALSE;
    }
    $rowAssoc = $this->data[$this->currentRowIndex];
    unset($this->data[$this->currentRowIndex]);
    return $this->assocToFetchMode($rowAssoc, $mode, $fetchOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed(int $keyIndex = 0, int $valueIndex = 1): array {
    if (!isset($this->columnNames[$keyIndex]) || !isset($this->columnNames[$valueIndex])) {
      return [];
    }

    $key = $this->columnNames[$keyIndex];
    $value = $this->columnNames[$valueIndex];

    $result = [];
    while ($row = $this->fetch(FetchAs::Associative, $this->fetchOptions)) {
      $result[$row[$key]] = $row[$value];
    }
    return $result;
  }

}
