<?php

namespace Drupal\Core\Database;

/**
 * StatementInterface iterator trait.
 *
 * Implements the methods required by StatementInterface objects that implement
 * the \Iterator interface.
 */
trait StatementIteratorTrait {

  /**
   * Traces if rows can be fetched from the resultset.
   */
  private bool $isResultsetIterable = FALSE;

  /**
   * The current row, retrieved in the current fetch format.
   */
  private mixed $resultsetRow = NULL;

  /**
   * The key of the current row.
   *
   * This keeps the index of rows fetched from the underlying statement. It is
   * set to -1 when no rows have been fetched yet.
   */
  private int $resultsetKey = -1;

  /**
   * Informs the iterator whether rows can be fetched from the resultset.
   *
   * @param bool $valid
   *   The result of the execution of the client statement.
   */
  protected function markResultsetIterable(bool $valid): void {
    $this->isResultsetIterable = $valid;
    $this->resultsetRow = NULL;
    if ($valid === TRUE) {
      $this->resultsetKey = -1;
    }
  }

  /**
   * Sets the current resultset row for the iterator, and increments the key.
   *
   * @param mixed $row
   *   The last row fetched from the client statement.
   */
  protected function setResultsetCurrentRow(mixed $row): void {
    $this->resultsetRow = $row;
    $this->resultsetKey++;
  }

  /**
   * Returns the row index of the current element in the resultset.
   *
   * @return int
   *   The row index of the current element in the resultset.
   */
  protected function getResultsetCurrentRowIndex(): int {
    return $this->resultsetKey;
  }

  /**
   * Informs the iterator that no more rows can be fetched from the resultset.
   */
  protected function markResultsetFetchingComplete(): void {
    $this->markResultsetIterable(FALSE);
  }

  /**
   * Returns the current element.
   *
   * @see https://www.php.net/manual/en/iterator.current.php
   *
   * @internal This method should not be called directly.
   */
  public function current(): mixed {
    return $this->resultsetRow;
  }

  /**
   * Returns the key of the current element.
   *
   * @see https://www.php.net/manual/en/iterator.key.php
   *
   * @internal This method should not be called directly.
   */
  public function key(): mixed {
    return $this->resultsetKey;
  }

  /**
   * Rewinds back to the first element of the Iterator.
   *
   * This is the first method called when starting a foreach loop. It will not
   * be executed after foreach loops.
   *
   * @see https://www.php.net/manual/en/iterator.rewind.php
   *
   * @internal This method should not be called directly.
   */
  public function rewind(): void {
    // Nothing to do: our DatabaseStatement can't be rewound. Error out when
    // attempted.
    // @todo convert the error to an exception in Drupal 11.
    if ($this->resultsetKey >= 0) {
      trigger_error('Attempted rewinding a StatementInterface object when fetching has already started. Refactor your code to avoid rewinding statement objects.', E_USER_WARNING);
      $this->markResultsetIterable(FALSE);
    }
  }

  /**
   * Moves the current position to the next element.
   *
   * This method is called after each foreach loop.
   *
   * @see https://www.php.net/manual/en/iterator.next.php
   *
   * @internal This method should not be called directly.
   */
  public function next(): void {
    $this->fetch();
  }

  /**
   * Checks if current position is valid.
   *
   * This method is called after ::rewind() and ::next() to check if the
   * current position is valid.
   *
   * @see https://www.php.net/manual/en/iterator.valid.php
   *
   * @internal This method should not be called directly.
   */
  public function valid(): bool {
    if ($this->isResultsetIterable && $this->resultsetKey === -1) {
      $this->fetch();
    }
    return $this->isResultsetIterable;
  }

}
