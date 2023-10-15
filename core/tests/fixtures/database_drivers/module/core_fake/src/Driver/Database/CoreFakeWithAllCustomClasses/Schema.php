<?php

namespace Drupal\core_fake\Driver\Database\CoreFakeWithAllCustomClasses;

use Drupal\Core\Database\Schema as DatabaseSchema;

/**
 * CoreFakeWithAllCustomClasses implementation of \Drupal\Core\Database\Schema.
 */
class Schema extends DatabaseSchema {

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeMap() {}

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $new_name) {}

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {}

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $spec, $keys_new = []) {}

  /**
   * {@inheritdoc}
   */
  public function dropField($table, $field) {}

  /**
   * {@inheritdoc}
   */
  public function indexExists($table, $name) {}

  /**
   * {@inheritdoc}
   */
  public function addPrimaryKey($table, $fields) {}

  /**
   * {@inheritdoc}
   */
  public function dropPrimaryKey($table) {}

  /**
   * {@inheritdoc}
   */
  public function addUniqueKey($table, $name, $fields) {}

  /**
   * {@inheritdoc}
   */
  public function dropUniqueKey($table, $name) {}

  /**
   * {@inheritdoc}
   */
  public function addIndex($table, $name, $fields, array $spec) {}

  /**
   * {@inheritdoc}
   */
  public function dropIndex($table, $name) {}

  /**
   * {@inheritdoc}
   */
  public function changeField($table, $field, $field_new, $spec, $keys_new = []) {}

}
