<?php

/**
 * @file
 * Contains \Drupal\Core\Database\StatementEmpty.
 */

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

  public function execute($args = array(), $options = array()) {
    return FALSE;
  }

  public function getQueryString() {
    return '';
  }

  public function rowCount() {
    if ($this->allowRowCount) {
      return 0;
    }
    throw new RowCountException();
  }

  public function setFetchMode($mode, $a1 = NULL, $a2 = array()) {
    return;
  }

  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL) {
    return NULL;
  }

  public function fetchField($index = 0) {
    return NULL;
  }

  public function fetchObject() {
    return NULL;
  }

  public function fetchAssoc() {
    return NULL;
  }

  function fetchAll($mode = NULL, $column_index = NULL, array $constructor_arguments = array()) {
    return array();
  }

  public function fetchCol($index = 0) {
    return array();
  }

  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    return array();
  }

  public function fetchAllAssoc($key, $fetch = NULL) {
    return array();
  }

  /* Implementations of Iterator. */

  public function current() {
    return NULL;
  }

  public function key() {
    return NULL;
  }

  public function rewind() {
    // Nothing to do: our DatabaseStatement can't be rewound.
  }

  public function next() {
    // Do nothing, since this is an always-empty implementation.
  }

  public function valid() {
    return FALSE;
  }
}
