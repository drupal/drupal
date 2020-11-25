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
  protected $rowCountEnabled = FALSE;

  /**
   * Implements the magic __get() method.
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Access the
   *   client-level statement object via ::getClientStatement().
   *
   * @see https://www.drupal.org/node/3177488
   */
  public function __get($name) {
    if ($name === 'allowRowCount') {
      @trigger_error("StatementEmpty::allowRowCount should not be accessed in drupal:9.2.0 and will error in drupal:10.0.0. TODO. See https://www.drupal.org/node/TODO", E_USER_DEPRECATED);
      return $this->rowCountEnabled;
    }
  }

  /**
   * Implements the magic __set() method.
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Access the
   *   client-level statement object via ::getClientStatement().
   *
   * @see https://www.drupal.org/node/3177488
   */
  public function __set($name, $value) {
    if ($name === 'allowRowCount') {
      @trigger_error("StatementEmpty::allowRowCount should not be written in drupal:9.2.0 and will error in drupal:10.0.0. TODO. See https://www.drupal.org/node/TODO", E_USER_DEPRECATED);
      $this->rowCountEnabled = $value;
    }
  }

  /**
   * Returns the target connection this statement is associated with.
   *
   * @return string|null
   *   The target connection string of this statement, or NULL if no target is
   *   set.
   */
  public function getConnectionTarget(): ?string {
    return NULL;
  }

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
    if ($this->rowCountEnabled) {
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
  public function fetchObject() {
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
