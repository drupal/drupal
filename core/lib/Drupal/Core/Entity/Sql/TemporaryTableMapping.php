<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

@trigger_error(__NAMESPACE__ . '\TemporaryTableMapping is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the default table mapping with a prefix instead.', E_USER_DEPRECATED);

/**
 * Defines a temporary table mapping class.
 *
 * @deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the
 *   default table mapping with a prefix instead.
 */
class TemporaryTableMapping extends DefaultTableMapping {

  /**
   * {@inheritdoc}
   */
  protected function generateFieldTableName(FieldStorageDefinitionInterface $storage_definition, $revision) {
    return static::getTempTableName(parent::generateFieldTableName($storage_definition, $revision));
  }

  /**
   * Generates a temporary table name.
   *
   * The method accounts for a maximum table name length of 64 characters.
   *
   * @param string $table_name
   *   The initial table name.
   * @param string $prefix
   *   (optional) The prefix to use for the new table name. Defaults to 'tmp_'.
   *
   * @return string
   *   The final table name.
   */
  public static function getTempTableName($table_name, $prefix = 'tmp_') {
    $tmp_table_name = $prefix . $table_name;

    // Limit the string to 48 characters, keeping a 16 characters margin for db
    // prefixes.
    if (strlen($table_name) > 48) {
      $short_table_name = substr($table_name, 0, 34);
      $table_hash = substr(hash('sha256', $table_name), 0, 10);

      $tmp_table_name = $prefix . $short_table_name . $table_hash;
    }
    return $tmp_table_name;
  }

}
