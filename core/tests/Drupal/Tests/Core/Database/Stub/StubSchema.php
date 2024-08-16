<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database\Stub;

use Drupal\Core\Database\Schema as DatabaseSchema;

/**
 * A stub of the abstract Schema class for testing purposes.
 *
 * Includes minimal implementations of Schema's abstract methods.
 */
class StubSchema extends DatabaseSchema {

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeMap() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $new_name) {
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $spec, $keys_new = []) {
  }

  /**
   * {@inheritdoc}
   */
  public function dropField($table, $field) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function indexExists($table, $name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addPrimaryKey($table, $fields) {
  }

  /**
   * {@inheritdoc}
   */
  public function dropPrimaryKey($table) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addUniqueKey($table, $name, $fields) {
  }

  /**
   * {@inheritdoc}
   */
  public function dropUniqueKey($table, $name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex($table, $name, $fields, array $spec) {
  }

  /**
   * {@inheritdoc}
   */
  public function dropIndex($table, $name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function changeField($table, $field, $field_new, $spec, $keys_new = []) {
  }

}
