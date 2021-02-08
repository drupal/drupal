<?php

namespace Drupal\Core\Database;

/**
 * Empty implementation of a database statement.
 *
 * This class satisfies the requirements of being a database statement/result
 * object, but does not actually contain data.  It is useful when developers
 * need to safely return an "empty" result set without connecting to an actual
 * database.  Calling code can then treat it the same as if it were an actual
 * result set that happens to contain no records.
 *
 * @see \Drupal\search\SearchQuery
 */
class StatementEmpty implements \Iterator, StatementInterface {

  /**
   * Is rowCount() execution allowed.
   *
   * @var bool
   */
  public $allowRowCount = FALSE;

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount() {
    if ($this->allowRowCount) {
      return 0;
    }
    throw new RowCountException();
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode($mode, $a1 = NULL, $a2 = []) {}

  /**
   * {@inheritdoc}
   */
  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchField($index = 0) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchObject(string $class_name = NULL, array $constructor_arguments = NULL) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $constructor_arguments = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fetchCol($index = 0) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc($key, $fetch = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    // Nothing to do: our DatabaseStatement can't be rewound.
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    // Do nothing, since this is an always-empty implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return FALSE;
  }

}
