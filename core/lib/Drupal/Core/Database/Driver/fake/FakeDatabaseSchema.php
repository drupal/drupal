<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\fake\FakeDatabaseSchema.
 */

namespace Drupal\Core\Database\Driver\fake;

use Drupal\Core\Database\Schema;

class FakeDatabaseSchema extends Schema {

  /**
   * As set on MigrateSqlSourceTestCase::databaseContents.
   *
   * @var array
   */
  public $databaseContents;

  /**
   * Constructs a fake database schema.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows.
   */
  public function __construct(array &$database_contents) {
    $this->uniqueIdentifier = uniqid('', TRUE);

    // @todo Maybe we can generate an internal representation.
    $this->databaseContents = &$database_contents;
  }

  /**
   * {@inheritdoc}
   */
  public function tableExists($table) {
    return in_array($table, array_keys($this->databaseContents));
  }

  /**
   * {@inheritdoc}
   */
  public function prefixNonTable($table) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTableNameCondition($table_name, $operator = '=', $add_prefix = TRUE) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  protected function getPrefixInfo($table = 'default', $add_prefix = TRUE) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $spec, $keys_new = array()) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex($table, $name, $fields) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function addPrimaryKey($table, $fields) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function addUniqueKey($table, $name, $fields) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function changeField($table, $field, $field_new, $spec, $keys_new = array()) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * PHP magic __clone() method.
   */
  public function __clone() {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function createTable($name, $table) {
    #throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function dropField($table, $field) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function dropIndex($table, $name) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function dropPrimaryKey($table) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {
    unset($this->databaseContents[$table]);
  }

  /**
   * {@inheritdoc}
   */
  public function dropUniqueKey($table, $name) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists($table, $column) {
    if (!empty($this->databaseContents[$table])) {
      $row = reset($this->databaseContents[$table]);
      return isset($row[$column]);
    }
    else {
      throw new \Exception("Can't determine whether field exists with an empty / nonexistent table.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldNames($fields) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSetDefault($table, $field, $default) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSetNoDefault($table, $field) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function findTables($table_expression) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeMap() {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function indexExists($table, $name) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function nextPlaceholder() {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function prepareComment($comment, $length = NULL) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $new_name) {
    throw new \Exception(sprintf('Unsupported method "%s"', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function uniqueIdentifier() {
    return $this->uniqueIdentifier;
  }

}
